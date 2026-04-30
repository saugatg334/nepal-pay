<?php
declare(strict_types=1);
namespace NepalPay\Services;
use NepalPay\Core\Logger;
use \Database;
use Exception;

class ChatbotService
{
    private const INTENTS = [
        'balance' => ['balance','paisa','kitna','baki','available','khata','mero paisa'],
        'transaction'=>['transaction','txn','karobar','last','history','sent','received','bharkhar'],
        'send'=>['send','pathaune','bhejna','transfer','pathaun'],
        'bill'=>['bill','tirne','nea','ncell','ntc','water','internet','tv','bijuli'],
        'pin'=>['pin','password','forgot','reset','birse','lock','change'],
        'fraud'=>['fraud','chori','stolen','katyo','deducted','gayena','missing','hack'],
        'kyc'=>['kyc','verify','citizenship','document','parkhya','approved','pending'],
        'offer'=>['offer','cashback','discount','deal','bonus','reward','aaja'],
        'escalate'=>['human','agent','support','talk','manchi','representative'],
    ];
    private const ANGRY = ['fuck','shit','damn','madarch','chutiya','lutyo','mara','khatam'];
    private const NEGATIVE = ['angry','frustrated','scam','fraud','terrible','worst','hate','katyo','chori','dhoka','cheat','gayena'];
    
    private ?int $userId;
    private string $sessionId;
    
    public function __construct(?int $userId=null){
        $this->userId=$userId;
        $this->sessionId=session_id()?:bin2hex(random_bytes(16));
    }
    
    public function processMessage(string $msg):array{
        $start=microtime(true);
        $msg=trim($msg);
        $clean=strtolower(preg_replace('/[^a-z0-9\s]/','',$msg));
        $lang=$this->detectLang($msg);
        $sentiment=$this->detectSentiment($msg);
        $intent=$this->detectIntent($clean);
        
        $resp=match($intent){
            'balance'=>$this->doBalance($lang),
            'transaction'=>$this->doTransaction($lang),
            'send'=>$this->doSend($lang),
            'bill'=>$this->doBill($lang),
            'pin'=>$this->doPin($lang),
            'fraud'=>$this->doFraud($msg,$lang),
            'kyc'=>$this->doKyc($lang),
            'offer'=>$this->doOffer($lang),
            'escalate'=>$this->doEscalate($msg,$lang),
            default=>$this->doFallback($clean,$lang),
        };
        
        if($sentiment==='angry' && in_array($intent,['fraud','escalate'])){
            $this->alertSecurity($msg);
        }
        
        $this->logConv($msg,$resp['text'],$intent,$lang,$sentiment,$start);
        $resp['intent']=$intent;
        $resp['language']=$lang;
        $resp['sentiment']=$sentiment;
        $resp['typing_ms']=min(strlen($resp['text'])*15,2000);
        return $resp;
    }
    
    private function detectLang(string $msg):string{
        if(preg_match('/[\\x{0900}-\\x{097F}]/u',$msg)) return 'np';
        $roman=['kati','mero','cha','paisa','pathaune','tirne','kasari','kina','bhayo','bho'];
        foreach($roman as $w){ if(stripos($msg,$w)!==false) return 'roman_np'; }
        return 'en';
    }
    
    private function detectSentiment(string $msg):string{
        $l=strtolower($msg);
        foreach(self::ANGRY as $w){ if(strpos($l,$w)!==false) return 'angry'; }
        foreach(self::NEGATIVE as $w){ if(strpos($l,$w)!==false) return 'negative'; }
        return 'neutral';
    }
    
    private function detectIntent(string $clean):string{
        foreach(self::INTENTS as $intent=>$keywords){
            foreach($keywords as $kw){ if(strpos($clean,$kw)!==false) return $intent; }
        }
        return 'unknown';
    }
    
    private function doBalance(string $lang):array{
        if(!$this->userId) return $this->needLogin($lang);
        $w=Database::fetch("SELECT balance FROM wallets WHERE user_id=? AND is_active=1",[$this->userId]);
        $bal=number_format((float)($w['balance']??0),2);
        return match($lang){
            'np'=>['text'=>"तपाईंको ब्यालेन्स: **रू. {$bal}**",'quick_replies'=>['कारोबार हेर्ने','पैसा पठाउने']],
            'roman_np'=>['text'=>"Tapaiko balance: **Rs. {$bal}**",'quick_replies'=>['Karobar herne','Paisa pathaune']],
            default=>['text'=>"Your balance: **NPR {$bal}**",'quick_replies'=>['View Transactions','Send Money']],
        };
    }
    
    private function doTransaction(string $lang):array{
        if(!$this->userId) return $this->needLogin($lang);
        $txns=Database::fetchAll("SELECT * FROM transactions WHERE sender_id=? OR receiver_id=? ORDER BY created_at DESC LIMIT 5",[$this->userId,$this->userId]);
        if(empty($txns)) return match($lang){
            'np'=>['text'=>'कुनै कारोबार भेटिएन। पहिलो कारोबार गर्नुहोस्!','quick_replies'=>['पैसा पठाउने']],
            'roman_np'=>['text'=>'Kunai karobar bhetiyena. Pahilo karobar garnuhos!','quick_replies'=>['Paisa pathaune']],
            default=>['text'=>'No transactions yet. Make your first one!','quick_replies'=>['Send Money']],
        };
        $lines=[];
        foreach($txns as $t){
            $amt=number_format((float)$t['amount'],2);
            $type=$t['type']; $date=date('M d',strtotime($t['created_at']));
            $emoji=match($type){'send'=>'📤','receive'=>'📥','add_money'=>'➕','withdraw'=>'🏧','bill_payment'=>'💡',default=>'💸'};
            $lines[]="{$emoji} NPR {$amt} — {$type} ({$date})";
        }
        $hdr=match($lang){'np'=>'भर्खरका कारोबार:','roman_np'=>'Bharkharka karobar:',default=>'Recent transactions:'};
        return ['text'=>$hdr."\n\n".implode("\n",$lines),'quick_replies'=>['Send Money','Pay Bill']];
    }
    
    private function doSend(string $lang):array{
        return match($lang){
            'np'=>['text'=>"पैसा पठाउन:\n1. **Send Money** मा क्लिक गर्नुहोस्\n2. प्राप्तकर्ताको फोन/वालेट नम्बर\n3. रकम (न्यूनतम रु. १०)\n4. PIN ले यकिन गर्नुहोस्\n\n✅ P2P मा शुल्क छैन!",'quick_replies'=>['Send Money','Reset PIN']],
            'roman_np'=>['text'=>"Paisa pathauna:\n1. **Send Money** ma click\n2. praptakarta ko phone/wallet\n3. rakam (minimum Rs. 10)\n4. PIN le yakin\n\n✅ P2P ma shulk chaina!",'quick_replies'=>['Send Money','Reset PIN']],
            default=>['text'=>"To send money:\n1. Click **Send Money**\n2. Enter recipient phone/wallet\n3. Amount (min NPR 10)\n4. Confirm with PIN\n\n✅ Zero P2P fees!",'quick_replies'=>['Send Now','Reset PIN']],
        };
    }
    
    private function doBill(string $lang):array{
        return match($lang){
            'np'=>['text'=>"बिल तिर्न उपलब्ध प्रदायकहरू:\n💡 NEA (बिजुली)\n📱 NTC / Ncell\n🌐 WorldLink / Vianet\n📺 DishHome\n💧 Khanepani\n\n२% क्यासब्याक कमाउनुहोस्!",'quick_replies'=>['NEA Bill','Mobile Recharge','Internet Bill']],
            default=>['text'=>"Available bill providers:\n💡 NEA Electricity\n📱 NTC / Ncell\n🌐 WorldLink / Vianet\n📺 DishHome\n💧 Water (Khanepani)\n\nEarn up to 2% cashback!",'quick_replies'=>['NEA Bill','Mobile Recharge','Internet Bill']],
        };
    }
    
    private function doPin(string $lang):array{
        return match($lang){
            'np'=>['text'=>"PIN मद्दत:\n• PIN बिर्सनुभयो? → **Security Settings** → Reset PIN (OTP आवश्यक)\n• PIN Lock? → ३० मिनेट पर्खनुहोस्\n• PIN परिवर्तन → Security मा जानुहोस्\n\n🔒 PIN कहिल्यै नदिनुहोस्!",'quick_replies'=>['Reset PIN','Security Settings']],
            default=>['text'=>"PIN Help:\n• Forgot PIN? → **Security Settings** → Reset PIN (OTP required)\n• PIN Locked? → Wait 30 minutes\n• Change PIN → Go to Security\n\n🔒 Never share your PIN!",'quick_replies'=>['Reset PIN','Security Settings']],
        };
    }
    
    private function doFraud(string $msg,string $lang):array{
        if($this->userId) $this->createTicket('fraud','Fraud Report: '.$msg,'critical');
        return match($lang){
            'np'=>['text'=>"⚠️ यो गम्भीर कुरा हो। हामीले तपाईंको रिपोर्ट रेकर्ड गरेका छौं।\n\nसम्पर्क नम्बर: ९८०-XXXXXXX\nइमेल: support@nepalpay.com\n\nप्राथमिकता सहयोग टिकट खोलियो।",'quick_replies'=>['सम्पर्क गर्ने','मुख्य पृष्ठ']],
            default=>['text'=>"⚠️ This is serious. We've logged your report.\n\nHotline: 980-XXXXXXX\nEmail: support@nepalpay.com\n\nPriority support ticket created.",'quick_replies'=>['Call Support','Go Home']],
        };
    }
    
    private function doKyc(string $lang):array{
        return match($lang){
            'np'=>['text'=>"KYC प्रमाणीकरण:\n1. प्रोफाइल → KYC Verification\n2. नागरिकता (अगाडि र पछाडि) अपलोड\n3. सेल्फी अपलोड\n4. २४-४८ घण्टा पर्खनुहोस्\n\nरू. २५,०००+ कारोबारका लागि अनिवार्य।",'quick_replies'=>['KYC Upload','Check Status']],
            default=>['text'=>"KYC Verification:\n1. Profile → KYC Verification\n2. Upload citizenship (front & back)\n3. Upload selfie\n4. Wait 24-48 hours\n\nRequired for transactions above NPR 25,000.",'quick_replies'=>['Upload KYC','Check Status']],
        };
    }
    
    private function doOffer(string $lang):array{
        return match($lang){
            'np'=>['text'=>"🎁 आजका अफरहरू:\n• बिल: २% क्यासब्याक\n• P2P: पहिलो ५ वटामा शुल्क छैन\n• मर्चेन्ट: १०% छुट\n• रिचार्ज: Ncell २%, NTC १.५%\n\nथप अफरहरूको लागि **Offers** ट्याब हेर्नुहोस्!",'quick_replies'=>['Bill Pay','Recharge']],
            default=>['text'=>"🎁 Today's Offers:\n• Bills: 2% cashback\n• P2P: Zero fees (first 5)\n• Merchants: 10% off\n• Recharge: Ncell 2%, NTC 1.5%\n\nSee **Offers** tab for more!",'quick_replies'=>['Pay Bill','Recharge']],
        };
    }
    
    private function doEscalate(string $msg,string $lang):array{
        if($this->userId) $this->createTicket('technical','Escalated from chatbot: '.$msg,'high');
        return match($lang){
            'np'=>['text'=>"👤 मानव एजेन्टसँग जोडिँदैछु...\n\nतपाईंको समस्या: _{$msg}_\n\nसहयोग टिकट #NP".rand(10000,99999)." बनाइयो।\n⏳ औसत प्रतिक्रिया समय: १५ मिनेट।",'quick_replies'=>['मुख्य पृष्ठ','फेरी प्रयास गर्ने']],
            default=>['text'=>"👤 Connecting to human agent...\n\nYour issue: _{$msg}_\n\nSupport ticket #NP".rand(10000,99999)." created.\n⏳ Average response: 15 minutes.",'quick_replies'=>['Home','Try Again']],
        };
    }
    
    private function doFallback(string $clean,string $lang):array{
        $faq=Database::fetch("SELECT * FROM faq_data WHERE is_active=1 AND JSON_OVERLAPS(keywords,JSON_ARRAY(?)) ORDER BY priority LIMIT 1",[$clean]);
        if($faq){
            $ans=$faq['answer_'.($lang==='roman_np'?'en':$lang)] ?? $faq['answer_en'];
            $ans=str_replace('{balance}',number_format((float)(Database::fetch("SELECT balance FROM wallets WHERE user_id=?",[$this->userId])['balance']??0),2),$ans);
            return ['text'=>$ans,'quick_replies'=>['Talk to Human','Main Menu']];
        }
        return match($lang){
            'np'=>['text'=>"माफ गर्नुहोस्, मैले बुझिनँ। कृपया फरक तरिकाले सोध्नुहोस् वा मानव सहयोग लिनुहोस्।\n\nउदाहरण: 'balance', 'send money', 'bill tirne'",'quick_replies'=>['मानव सहयोग','मुख्य पृष्ठ']],
            default=>['text'=>"Sorry, I didn't understand. Please rephrase or contact human support.\n\nTry: 'balance', 'send money', 'pay bill'",'quick_replies'=>['Talk to Human','Main Menu']],
        };
    }
    
    private function needLogin(string $lang):array{
        return match($lang){
            'np'=>['text'=>'कृपया पहिले लगइन गर्नुहोस्। म तपाईंको डेटा देखाउन सक्दिन।','quick_replies'=>['Login']],
            default=>['text'=>'Please log in first. I cannot show your data without authentication.','quick_replies'=>['Login']],
        };
    }
    
    private function errorResponse(string $lang):array{
        return match($lang){
            'np'=>['text'=>'माफ गर्नुहोस्, केही गडबड भयो। पुन: प्रयास गर्नुहोस्।'],
            default=>['text'=>'Sorry, something went wrong. Please try again.'],
        };
    }
    
    private function createTicket(string $cat,string $subj,string $prio):void{
        if(!$this->userId) return;
        $num='NP'.date('Ymd').rand(1000,9999);
        Database::query("INSERT INTO support_tickets (user_id,ticket_number,category,subject,priority,status) VALUES (?,?,?,?,?,'open')",[$this->userId,$num,$cat,$subj,$prio]);
    }
    
    private function alertSecurity(string $msg):void{
        if(!$this->userId) return;
        Logger::security('Chatbot fraud alert triggered',[
            'user_id'=>$this->userId,'message'=>$msg,'ip'=>$_SERVER['REMOTE_ADDR']??'unknown'
        ]);
    }
    
    private function logConv(string $msg,string $resp,string $intent,string $lang,string $sentiment,float $start):void{
        if(!$this->userId) return;
        $ms=round((microtime(true)-$start)*1000);
        Database::query("INSERT INTO chatbot_conversations (user_id,session_id,user_message,bot_response,intent_detected,language,sentiment,response_time_ms) VALUES (?,?,?,?,?,?,?,?)",[
            $this->userId,$this->sessionId,$msg,$resp,$intent,$lang,$sentiment,$ms
        ]);
    }
}
