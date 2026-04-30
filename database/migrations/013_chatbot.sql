-- NepalPay Sathi — Smart Chatbot System
-- Migration 013: Chatbot tables for AI assistant
-- Run after all previous migrations (001-012)

-- ============================================
-- 1. CHATBOT CONVERSATIONS
-- ============================================
CREATE TABLE IF NOT EXISTS chatbot_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'Authenticated user ID',
    session_id VARCHAR(64) NOT NULL COMMENT 'Browser session fingerprint',
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    intent_detected VARCHAR(50) DEFAULT 'unknown' COMMENT 'AI intent classification',
    language VARCHAR(10) DEFAULT 'en' COMMENT 'en, np, roman_np',
    sentiment VARCHAR(20) DEFAULT 'neutral' COMMENT 'positive, neutral, negative, angry',
    is_escalated TINYINT(1) DEFAULT 0 COMMENT 'Handed to human support',
    ticket_id INT NULL COMMENT 'Linked support ticket if escalated',
    response_time_ms INT DEFAULT 0 COMMENT 'Performance tracking',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_intent (intent_detected),
    INDEX idx_sentiment (sentiment),
    INDEX idx_created (created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chatbot message history';

-- ============================================
-- 2. FAQ KNOWLEDGE BASE
-- ============================================
CREATE TABLE IF NOT EXISTS faq_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL COMMENT 'balance, send, bill, pin, fraud, kyc, offer, general',
    question_en TEXT NOT NULL COMMENT 'English question pattern',
    question_np TEXT NULL COMMENT 'Nepali question pattern',
    question_roman TEXT NULL COMMENT 'Roman Nepali question pattern',
    keywords JSON NOT NULL COMMENT 'Array of trigger words',
    answer_en TEXT NOT NULL,
    answer_np TEXT NULL,
    answer_roman TEXT NULL,
    action_type ENUM('text', 'redirect', 'api_call', 'ticket') DEFAULT 'text',
    action_payload VARCHAR(255) NULL COMMENT 'URL or API endpoint',
    priority INT DEFAULT 100 COMMENT 'Lower = higher priority',
    is_active TINYINT(1) DEFAULT 1,
    usage_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority),
    FULLTEXT INDEX idx_question_en (question_en),
    FULLTEXT INDEX idx_question_np (question_np)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chatbot knowledge base';

-- Seed FAQ data
INSERT INTO faq_data (category, question_en, question_np, question_roman, keywords, answer_en, answer_np, answer_roman, priority) VALUES
('balance', 'What is my balance?', 'मेरो ब्यालेन्स कति छ?', 'mero balance kati cha', '["balance","paisa","balance kati","money"]', 
 'Your current wallet balance is NPR {balance}. You can also check it on your dashboard.', 
 'तपाईंको हालको वालेट ब्यालेन्स रु. {balance} हो। ड्यासबोर्डमा पनि हेर्न सक्नुहुन्छ।',
 'tapaiko halko wallet balance Rs. {balance} ho. Dashboard ma pani herna saknuhuncha.', 10),

('balance', 'How much money do I have?', 'मसँग कति पैसा छ?', 'ma sanga kati paisa cha', '["money","have","kitna","kati"]', 
 'You currently have NPR {balance} in your NepalPay wallet.', 
 'तपाईंको नेपालपे वालेटमा हाल रु. {balance} छ।',
 'tapaiko NepalPay wallet ma halko Rs. {balance} cha.', 10),

('transaction', 'Show my last transaction', 'मेरो अन्तिम कारोबार देखाउनुहोस्', 'mero antim karobar dekhaunuhos', '["transaction","last","recent","history","payment"]', 
 'Your last transaction: {last_txn}. View all transactions in the History tab.', 
 'तपाईंको अन्तिम कारोबार: {last_txn}। सबै कारोबार हिस्ट्री ट्याबमा हेर्नुहोस्।',
 'tapaiko antim karobar: {last_txn}. Sabai karobar history tab ma hernuhos.', 10),

('transaction', 'Recent payments', 'भर्खरका भुक्तानीहरू', 'bharkharkaa bhuktaniharu', '["payment","paid","sent","transfer"]', 
 'Here are your recent transactions: {recent_txns}', 
 'यहाँ तपाईंका भर्खरका कारोबारहरू छन्: {recent_txns}',
 'yaha tapaika bharkharka karobarharu chan: {recent_txns}', 10),

('send', 'How to send money?', 'पैसा कसरी पठाउने?', 'paisa kasari pathaune', '["send","pathaune","transfer","bhejna"]', 
 'To send money: 1) Click "Send Money" 2) Enter recipient phone/wallet number 3) Enter amount 4) Confirm with your Transaction PIN. Minimum: NPR 10. No fees for P2P!', 
 'पैसा पठाउन: १) "पैसा पठाउनुहोस्" मा क्लिक गर्नुहोस् २) प्राप्तकर्ताको फोन/वालेट नम्बर ३) रकम ४) ट्रान्जेक्सन पिनले यकिन गर्नुहोस्। न्यूनतम: रु. १०। P2P मा शुल्क छैन!',
 'paisa pathauna: 1) "Send Money" ma click garnuhos 2) praptakartako phone/wallet number 3) rakam 4) transaction PIN le yakin garnuhos. Minimum: Rs. 10. P2P ma shulk chaina!', 10),

('send', 'Transfer failed why?', 'स्थानान्तरण किन असफल भयो?', 'sthantaran kina asafal bhayo', '["failed","fail","error","problem","kina"]', 
 'Transfer may fail due to: 1) Insufficient balance 2) Wrong recipient 3) Network issue 4) PIN incorrect (3 attempts = 30min lock). Check your balance and try again.', 
 'स्थानान्तरण यसकारण असफल हुन सक्छ: १) अपर्याप्त ब्यालेन्स २) गलत प्राप्तकर्ता ३) नेटवर्क समस्या ४) गलत पिन (३ पटक = ३० मिनेट लक)। ब्यालेन्स जाँच गरी पुन: प्रयास गर्नुहोस्।',
 'sthantaran yaskaran asafal huna sakcha: 1) aparyapt balance 2) galat praptakarta 3) network samasya 4) galat PIN (3 patak = 30 minute lock). Balance jac gari puna prayas garnuhos.', 10),

('bill', 'How to pay bills?', 'बिल कसरी तिर्ने?', 'bill kasari tirne', '["bill","tirne","pay","electricity","nea","ncell","ntc"]', 
 'To pay bills: 1) Click "Pay Bill" 2) Select provider (NEA, NTC, Ncell, WorldLink, etc.) 3) Enter customer ID 4) Verify amount 5) Pay with PIN. Earn up to 2% cashback!', 
 'बिल तिर्न: १) "बिल तिर्नुहोस्" २) प्रदायक छान्नुहोस् (NEA, NTC, Ncell, WorldLink आदि) ३) ग्राहक आईडी ४) रकम यकिन ५) PIN ले तिर्नुहोस्। २% क्यासब्याक कमाउनुहोस्!',
 'bill tirna: 1) "Pay Bill" 2) pradayak channuhos 3) grahak ID 4) rakam yakin 5) PIN le tirnuhos. 2% cashback kamaunuhos!', 10),

('bill', 'NEA bill payment', 'NEA बिल तिर्ने', 'NEA bill tirne', '["nea","electricity","bijuli","current"]', 
 'Pay NEA: Go to Bill Payment → Select NEA → Enter Consumer ID → View due amount → Pay with PIN. Keep your consumer ID handy!', 
 'NEA तिर्न: बिल पेमेन्ट → NEA छान्नुहोस् → कन्जुमर आईडी → बक्यौता रकम → PIN ले तिर्नुहोस्।',
 'NEA tirna: Bill Payment → NEA channuhos → Consumer ID → bakauta rakam → PIN le tirnuhos.', 10),

('pin', 'Forgot transaction PIN', 'ट्रान्जेक्सन पिन बिर्सिएँ', 'transaction pin birsiem', '["forgot","pin","reset","birse","password"]', 
 'To reset your Transaction PIN: 1) Go to Security Settings 2) Click "Reset PIN" 3) Verify with OTP sent to your phone 4) Set new 4-6 digit PIN. Never share your PIN!', 
 'ट्रान्जेक्सन पिन रिसेट गर्न: १) सुरक्षा सेटिङ २) "पिन रिसेट" ३) फोनमा आएको OTP ले यकिन ४) नयाँ ४-६ अंकको पिन। पिन कसैलाई नदिनुहोस्!',
 'transaction PIN reset garna: 1) Security Settings 2) "Reset PIN" 3) phone ma aayeko OTP le yakin 4) naya 4-6 ankako PIN. PIN kasailai nadinnuhos!', 10),

('pin', 'Account locked', 'खाता लक भयो', 'khata lock bhayo', '["locked","lock","bandha","bhayo"]', 
 'Your account is temporarily locked due to 3 failed PIN attempts. Wait 30 minutes or contact support. Do not try again until unlocked.', 
 '३ पटक गलत PIN प्रयोग गरेकोले तपाईंको खाता अस्थायी रूपमा लक भएको छ। ३० मिनेट पर्खनुहोस् वा सहयोग टिमलाई सम्पर्क गर्नुहोस्।',
 '3 patak galat PIN prayog garekole tapaiko khata asthai rupma lock bhayeko cha. 30 minute parkhnuhos wa sahayog timlai samparka garnuhos.', 10),

('fraud', 'Money deducted but not sent', 'पैसा कट्यो तर गएन', 'paisa katyo tara gayena', '["fraud","deducted","katyo","stolen","gayena"]', 
 'This is serious. Please: 1) Check transaction history 2) If no record exists, the transaction may have rolled back (money returns in 24hrs) 3) If confirmed fraud, we will investigate immediately. Creating a priority support ticket for you now.', 
 'यो गम्भीर कुरा हो। कृपया: १) कारोबार हिस्ट्री जाँच्नुहोस् २) रेकर्ड छैन भने २४ घण्टामा पैसा फर्कन्छ ३) धोका भए हामी तुरुन्त अनुसन्धान गर्छौं। प्राथमिकता सहयोग टिकट खोल्दैछौं।',
 'yo gambhir kura ho. Kripaya: 1) karobar history jacnnuhos 2) record chaina bhane 24 ghantama paisa farkancha 3) dhoka bhaye hami turunta anusandhan garchhau. Prathamikta sahayog ticket kholaichhau.', 5),

('fraud', 'Suspicious login', 'शंकास्पद लगइन', 'shankaspad login', '["suspicious","login","device","anauhane"]', 
 'We take security seriously. Go to Security Settings → Login Devices to see all active sessions. Click "Logout All Devices" if you see unknown activity. Enable 2FA for extra protection.', 
 'सुरक्षा हाम्रो प्राथमिकता हो। सुरक्षा सेटिङ → लगइन डिभाइसमा गएर सबै सक्रिय सत्र हेर्नुहोस्। अपरिचित गतिविधि देखे "सबै डिभाइसबाट लगआउट" गर्नुहोस्। 2FA सक्रिय गर्नुहोस्।',
 'suraksha hamro prathamikta ho. Security Settings → Login Devices ma gayera sabai sakriya satra hernuhos. aparichit gatividhi dekhe "sabai devicebata logout" garnuhos. 2FA sakriya garnuhos.', 5),

('kyc', 'How to verify KYC?', 'KYC कसरी गर्ने?', 'KYC kasari garne', '["kyc","verify","citizenship","document","parkhya"]', 
 'To complete KYC: 1) Go to Profile → KYC Verification 2) Upload citizenship front & back 3) Upload selfie 4) Wait 24-48hrs for approval. Required for transactions above NPR 25,000.', 
 'KYC पूरा गर्न: १) प्रोफाइल → KYC भेरिफिकेसन २) नागरिकता अगाडि र पछाडि अपलोड ३) सेल्फी अपलोड ४) २४-४८ घण्टामा अनुमोदन। रु. २५,००० भन्दा माथिको कारोबारका लागि अनिवार्य।',
 'KYC pura garna: 1) Profile → KYC Verification 2) nagarikta agadi ra pachadi upload 3) selfie upload 4) 24-48 ghantama anumodan. Rs. 25,000 bhanda mathiko karobarka lagi anivarya.', 10),

('kyc', 'Why KYC pending?', 'KYC पेन्डिङ किन?', 'KYC pending kina', '["pending","why","kina","approve"]', 
 'KYC verification takes 24-48 hours. If pending longer: 1) Check document clarity 2) Ensure citizenship number is visible 3) Selfie must match citizenship photo. Contact support if delayed beyond 72 hours.', 
 'KYC प्रमाणीकरण २४-४८ घण्टा लाग्छ। लामो समय भए: १) कागजात स्पष्टता जाँच्नुहोस् २) नागरिकता नम्बर देखिनुपर्छ ३) सेल्फी र नागरिकताको फोटो मिल्नुपर्छ। ७२ घण्टा नाघे सम्पर्क गर्नुहोस्।',
 'KYC pramanikaran 24-48 ghanta lagchha. Lamo samaya bhaye: 1) kagajat spastata jacnnuhos 2) nagarikta number dekhinuparcha 3) selfie ra nagariktako photo milnuparcha. 72 ghanta nage samparka garnuhos.', 10),

('offer', 'What offers today?', 'आज के अफर छ?', 'aaja ke offer cha', '["offer","cashback","discount","deal","aaja"]', 
 'Today''s offers: 🎁 2% cashback on all bill payments | 🎁 Zero fees on first 5 P2P transfers | 🎁 10% off at partnered merchants. Check the Offers tab for more!', 
 'आजका अफरहरू: 🎁 सबै बिल पेमेन्टमा २% क्यासब्याक | 🎁 पहिलो ५ P2P मा शुल्क छैन | 🎁 साझेदार मर्चेन्टमा १०% छुट। थप अफरहरूको लागि अफर ट्याब हेर्नुहोस्!',
 'aajaka offerharu: 1) sabai bill payment ma 2% cashback 2) pahilo 5 P2P ma shulk chaina 3) sajhedar merchant ma 10% chhut. Thap offerharuko lagi offer tab hernuhos!', 10),

('offer', 'Recharge cashback', 'रिचार्ज क्यासब्याक', 'recharge cashback', '["recharge","topup","mobile","ncell","ntc"]', 
 'Mobile recharge cashback: NTC = 1.5% | Ncell = 2% | Smart Cell = 1%. Cashback credited within 24 hours. Minimum recharge: NPR 50.', 
 'मोबाइल रिचार्ज क्यासब्याक: NTC = १.५% | Ncell = २% | Smart Cell = १%। क्यासब्याक २४ घण्टाभित्र जम्मा हुन्छ। न्यूनतम रिचार्ज: रु. ५०।',
 'mobile recharge cashback: NTC = 1.5% | Ncell = 2% | Smart Cell = 1%. cashback 24 ghantabhitra jamma hunchha. Nyunatam recharge: Rs. 50.', 10),

('general', 'How are you?', 'तपाईंलाई कस्तो छ?', 'tapailai kasto cha', '["hello","hi","kasto","how","namaste"]', 
 'Namaste! 🙏 I am NepalPay Sathi, your smart assistant. I can help you check balance, send money, pay bills, reset PIN, and more. How may I assist you today?', 
 'नमस्ते! 🙏 म नेपालपे साथी, तपाईंको स्मार्ट सहायक। ब्यालेन्स जाँच, पैसा पठाउन, बिल तिर्न, पिन रिसेट र अरू मद्दत गर्न सक्छु। आज कसरी सहयोग गरौं?',
 'Namaste! ma NepalPay Sathi, tapaiko smart sahayak. Balance jac, paisa pathauna, bill tirna, PIN reset ra aru madat garna sakchu. Aaja kasari sahayog garau?', 20),

('general', 'Thank you', 'धन्यवाद', 'dhanyabad', '["thanks","thank","dhanyabad","dhanya"]', 
 'You''re welcome! 🙏 If you need anything else, I am here 24/7. Have a great day!', 
 'तपाईंलाई धन्यवाद! 🙏 अरू केही चाहिए म सधैं यहीं छु। राम्रो दिन होस्!',
 'tapaailai dhanyabad! Aru kehi chahiye ma sadhai yahi chhu. Ramro din hos!', 20),

('general', 'Talk to human', 'मान्छेसँग कुरा गर्ने', 'manchhesanga kura garne', '["human","agent","support","helpdesk","talk"]', 
 'I will connect you with a human agent. Please describe your issue briefly while I create a support ticket for you.', 
 'म तपाईंलाई मानव एजेन्टसँग जोड्दैछु। कृपया आफ्नो समस्या संक्षिप्तमा वर्णन गर्नुहोस्, म सहयोग टिकट बनाइरहेको छु।',
 'ma tapaailai manav agentasanga joddachhu. Kripaya aafno samasya sankshiptama barnan garnuhos, ma sahayog ticket banaireko chhu.', 5);

-- ============================================
-- 3. SUPPORT TICKETS
-- ============================================
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    category ENUM('balance','send','bill','pin','fraud','kyc','offer','technical','other') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
    assigned_to INT NULL COMMENT 'Admin user ID',
    chatbot_conversation_id INT NULL COMMENT 'Linked chatbot conversation',
    resolution_notes TEXT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_assigned (assigned_to),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Human support tickets from chatbot escalation';

-- ============================================
-- 4. CHATBOT ANALYTICS
-- ============================================
CREATE TABLE IF NOT EXISTS chatbot_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    total_conversations INT DEFAULT 0,
    total_messages INT DEFAULT 0,
    avg_response_time_ms INT DEFAULT 0,
    escalated_count INT DEFAULT 0,
    resolved_by_bot INT DEFAULT 0,
    satisfaction_score DECIMAL(3,2) DEFAULT 0.00,
    top_intent VARCHAR(50) NULL,
    angry_users_count INT DEFAULT 0,
    UNIQUE KEY idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily chatbot performance metrics';

-- Trigger: auto-update analytics on new conversation
DELIMITER //
CREATE TRIGGER IF NOT EXISTS trg_chatbot_analytics
AFTER INSERT ON chatbot_conversations
FOR EACH ROW
BEGIN
    INSERT INTO chatbot_analytics (date, total_conversations, total_messages)
    VALUES (CURDATE(), 1, 1)
    ON DUPLICATE KEY UPDATE
        total_conversations = total_conversations + 1,
        total_messages = total_messages + 1;
END//
DELIMITER ;
