/**
 * NepalPay Sathi — Smart Chatbot Frontend Engine
 * Production-grade JavaScript with voice, emoji, animations, security
 * @version 2.0.0
 */
(function(){
  'use strict';

  const SathiChat = {
    // Configuration
    config: {
      apiBase: window.APP_URL ? window.APP_URL + '/index.php' : '/index.php',
      csrfToken: window.SATHI_CSRF || '',
      userId: window.SATHI_USER || null,
      maxRetries: 3,
      typingDelay: 600,
      suggestions: [
        'Balance check',
        'Send money help',
        'Pay NEA bill',
        'Reset PIN',
        'Today\'s offers',
        'Talk to human'
      ]
    },

    // State
    state: {
      isOpen: false,
      isTyping: false,
      messages: [],
      retryCount: 0,
      recognition: null,
      isRecording: false
    },

    // DOM Elements cache
    els: {},

    /**
     * Initialize chatbot
     */
    init: function(){
      this.cacheElements();
      this.bindEvents();
      this.renderWelcome();
      this.loadHistory();
      
      // Initialize voice if available
      if('webkitSpeechRecognition' in window || 'SpeechRecognition' in window){
        this.initVoice();
      }
      
      console.log('?? NepalPay Sathi initialized');
    },

    cacheElements: function(){
      this.els.launcher = document.getElementById('sathi-launcher');
      this.els.chat = document.getElementById('sathi-chat');
      this.els.messages = document.getElementById('sathi-messages');
      this.els.input = document.getElementById('sathi-input');
      this.els.sendBtn = document.getElementById('sathi-send');
      this.els.micBtn = document.getElementById('sathi-mic');
      this.els.closeBtn = document.getElementById('sathi-close');
      this.els.minBtn = document.getElementById('sathi-minimize');
      this.els.quickReplies = document.getElementById('sathi-quick-replies');
      this.els.suggestions = document.getElementById('sathi-suggestions');
      this.els.badge = document.getElementById('sathi-badge');
    },

    bindEvents: function(){
      const self = this;
      
      // Launcher toggle
      this.els.launcher?.addEventListener('click', () => self.toggle());
      this.els.closeBtn?.addEventListener('click', () => self.close());
      this.els.minBtn?.addEventListener('click', () => self.minimize());
      
      // Input handling
      this.els.input?.addEventListener('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey){
          e.preventDefault();
          self.sendMessage(this.value.trim());
        }
      });
      
      this.els.sendBtn?.addEventListener('click', () => {
        self.sendMessage(self.els.input.value.trim());
      });
      
      // Mic button
      this.els.micBtn?.addEventListener('click', () => self.toggleVoice());
      
      // Emoji picker (simple toggle)
      document.getElementById('sathi-emoji')?.addEventListener('click', function(){
        self.toggleEmojiPicker();
      });
      
      // Quick replies delegation
      this.els.quickReplies?.addEventListener('click', function(e){
        if(e.target.classList.contains('sathi-quick-btn')){
          self.sendMessage(e.target.textContent);
        }
      });
      
      // Suggestions delegation
      this.els.suggestions?.addEventListener('click', function(e){
        if(e.target.classList.contains('sathi-suggestion-chip')){
          self.sendMessage(e.target.textContent);
        }
      });
      
      // Click outside to close (desktop only)
      document.addEventListener('click', function(e){
        if(self.state.isOpen && !self.els.chat.contains(e.target) && !self.els.launcher.contains(e.target)){
          if(window.innerWidth > 480) self.close();
        }
      });
    },

    /**
     * Toggle chat window
     */
    toggle: function(){
      this.state.isOpen = !this.state.isOpen;
      this.els.chat?.classList.toggle('open', this.state.isOpen);
      this.els.launcher?.classList.toggle('hidden', this.state.isOpen);
      
      if(this.state.isOpen){
        this.els.badge && (this.els.badge.style.display = 'none');
        setTimeout(() => this.els.input?.focus(), 300);
        this.scrollToBottom();
      }
    },

    close: function(){
      this.state.isOpen = false;
      this.els.chat?.classList.remove('open');
      this.els.launcher?.classList.remove('hidden');
    },

    minimize: function(){
      this.close();
    },

    /**
     * Send message to bot
     */
    sendMessage: function(text){
      if(!text || this.state.isTyping) return;
      
      // Clear input
      this.els.input.value = '';
      this.els.sendBtn.disabled = true;
      
      // Add user message
      this.addMessage('user', text);
      this.clearQuickReplies();
      
      // Show typing
      this.showTyping();
      
      // API call
      this.callBotAPI(text)
        .then(resp => {
          this.hideTyping();
          this.addMessage('bot', resp.response, resp.meta);
          if(resp.quick_replies?.length){
            this.showQuickReplies(resp.quick_replies);
          }
          this.els.sendBtn.disabled = false;
          this.state.retryCount = 0;
        })
        .catch(err => {
          this.hideTyping();
          this.addMessage('bot', '?? Sorry, I am having trouble connecting. Please try again.');
          this.els.sendBtn.disabled = false;
          console.error('Sathi API error:', err);
        });
    },

    /**
     * Call backend API
     */
    callBotAPI: async function(message){
      const formData = new FormData();
      formData.append('message', message);
      formData.append('csrf_token', this.config.csrfToken);
      
      const resp = await fetch(this.config.apiBase + '?page=chatbot&action=message', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      });
      
      if(!resp.ok){
        if(resp.status === 429){
          throw new Error('Rate limited');
        }
        throw new Error('HTTP ' + resp.status);
      }
      
      return await resp.json();
    },

    /**
     * Add message to chat
     */
    addMessage: function(role, text, meta){
      const div = document.createElement('div');
      div.className = 'sathi-msg ' + role;
      
      const time = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit', hour12: true
      });
      
      // Parse markdown-like formatting
      const formatted = this.formatText(text);
      
      div.innerHTML = `
        <div class="sathi-msg-bubble">${formatted}</div>
        <span class="sathi-msg-time">${time}</span>
      `;
      
      this.els.messages.appendChild(div);
      this.scrollToBottom();
      
      // Store in state
      this.state.messages.push({role, text, time, meta});
    },

    /**
     * Format text with basic markdown
     */
    formatText: function(text){
      return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code style="background:rgba(0,0,0,.08);padding:2px 6px;border-radius:4px;font-family:monospace;">$1</code>')
        .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" style="color:var(--sathi-primary);text-decoration:underline;">$1</a>')
        .replace(/\n/g, '<br>');
    },

    /**
     * Show typing indicator
     */
    showTyping: function(){
      this.state.isTyping = true;
      const div = document.createElement('div');
      div.className = 'sathi-typing';
      div.id = 'sathi-typing';
      div.innerHTML = `
        <div class="sathi-typing-dot"></div>
        <div class="sathi-typing-dot"></div>
        <div class="sathi-typing-dot"></div>
      `;
      this.els.messages.appendChild(div);
      this.scrollToBottom();
    },

    hideTyping: function(){
      this.state.isTyping = false;
      document.getElementById('sathi-typing')?.remove();
    },

    /**
     * Quick reply buttons
     */
    showQuickReplies: function(replies){
      this.els.quickReplies.innerHTML = replies.map(r => 
        `<button class="sathi-quick-btn">${this.escapeHtml(r)}</button>`
      ).join('');
      this.els.quickReplies.style.display = 'flex';
    },

    clearQuickReplies: function(){
      this.els.quickReplies.innerHTML = '';
      this.els.quickReplies.style.display = 'none';
    },

    /**
     * Render welcome message
     */
    renderWelcome: function(){
      const hour = new Date().getHours();
      const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
      
      const welcome = document.createElement('div');
      welcome.className = 'sathi-welcome';
      welcome.innerHTML = `
        <div class="sathi-welcome-avatar">??</div>
        <h4>${greeting}! I'm Sathi</h4>
        <p>Your NepalPay smart assistant. Ask me about balance, sending money, bills, or anything!</p>
        <span class="sathi-welcome-time">${new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true})}</span>
      `;
      this.els.messages.appendChild(welcome);
    },

    /**
     * Load chat history
     */
    loadHistory: async function(){
      if(!this.config.userId) return;
      
      try{
        const resp = await fetch(this.config.apiBase + '?page=chatbot&action=history&limit=10', {
          credentials: 'same-origin'
        });
        const data = await resp.json();
        
        if(data.success && data.messages?.length){
          // Clear welcome, show history
          this.els.messages.innerHTML = '';
          data.messages.forEach(m => {
            this.addMessage('user', m.user_message);
            this.addMessage('bot', m.bot_response);
          });
        }
      }catch(e){
        console.log('History load skipped:', e.message);
      }
    },

    /**
     * Voice input (Web Speech API)
     */
    initVoice: function(){
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      if(!SpeechRecognition) return;
      
      this.state.recognition = new SpeechRecognition();
      this.state.recognition.lang = 'en-US,ne-NP';
      this.state.recognition.continuous = false;
      this.state.recognition.interimResults = false;
      
      const self = this;
      this.state.recognition.onresult = function(e){
        const transcript = e.results[0][0].transcript;
        self.els.input.value = transcript;
        self.sendMessage(transcript);
      };
      
      this.state.recognition.onerror = function(e){
        console.log('Voice error:', e.error);
        self.stopRecording();
      };
      
      this.state.recognition.onend = function(){
        self.stopRecording();
      };
    },

    toggleVoice: function(){
      if(!this.state.recognition){
        alert('Voice input not supported in this browser. Try Chrome or Safari.');
        return;
      }
      
      if(this.state.isRecording){
        this.state.recognition.stop();
      }else{
        this.startRecording();
      }
    },

    startRecording: function(){
      this.state.isRecording = true;
      this.els.micBtn.classList.add('recording');
      this.els.input.placeholder = 'Listening...';
      try{
        this.state.recognition.start();
      }catch(e){
        this.stopRecording();
      }
    },

    stopRecording: function(){
      this.state.isRecording = false;
      this.els.micBtn.classList.remove('recording');
      this.els.input.placeholder = 'Type a message...';
    },

    /**
     * Emoji picker toggle
     */
    toggleEmojiPicker: function(){
      const emojis = ['??','??','??','??','??','??','??','??','??','??','??','??','??','??','??','??','??','??','??','??'];
      const picker = document.createElement('div');
      picker.style.cssText = 'position:absolute;bottom:70px;left:16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:8px;display:flex;flex-wrap:wrap;gap:4px;width:200px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:100;';
      
      emojis.forEach(emoji => {
        const btn = document.createElement('button');
        btn.textContent = emoji;
        btn.style.cssText = 'background:none;border:none;font-size:20px;cursor:pointer;padding:4px;border-radius:4px;';
        btn.onclick = () => {
          this.els.input.value += emoji;
          this.els.input.focus();
          picker.remove();
        };
        picker.appendChild(btn);
      });
      
      // Remove existing picker
      document.querySelector('.sathi-emoji-picker')?.remove();
      picker.className = 'sathi-emoji-picker';
      
      this.els.chat.appendChild(picker);
      setTimeout(() => {
        document.addEventListener('click', function close(e){
          if(!picker.contains(e.target)){
            picker.remove();
            document.removeEventListener('click', close);
          }
        });
      }, 100);
    },

    /**
     * Download chat history
     */
    downloadChat: function(){
      if(!this.config.userId){
        alert('Please log in to download chat history.');
        return;
      }
      window.open(this.config.apiBase + '?page=chatbot&action=download', '_blank');
    },

    scrollToBottom: function(){
      this.els.messages.scrollTop = this.els.messages.scrollHeight;
    },

    escapeHtml: function(text){
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  };

  // Auto-initialize when DOM is ready
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', () => SathiChat.init());
  }else{
    SathiChat.init();
  }

  // Expose to global scope for debugging
  window.SathiChat = SathiChat;
})();
