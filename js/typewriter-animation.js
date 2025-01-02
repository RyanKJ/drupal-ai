(function ($, Drupal) {
  'use strict';

  class TypewriterAnimation {
    constructor(element, text, speed = 30) {
      this.element = element;
      this.text = text;
      this.speed = speed;
      this.words = this.text.split(' ');
      this.currentWord = 0;
      this.isAnimating = false;
      this.onComplete = null;
    }

    start(onCompleteCallback) {
      if (this.isAnimating) return;
      this.isAnimating = true;
      this.onComplete = onCompleteCallback;
      this.element.innerHTML = '';
      this.animateWords();
    }

    animateWords() {
      if (this.currentWord >= this.words.length) {
        this.isAnimating = false;
        if (this.onComplete) {
          this.onComplete();
        }
        return;
      }

      const word = this.words[this.currentWord];
      const span = document.createElement('span');
      span.textContent = word + ' ';
      span.style.opacity = '0';
      this.element.appendChild(span);

      setTimeout(() => {
        span.style.opacity = '1';
        this.currentWord++;
        this.animateWords();
      }, this.speed * 1.53);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;
      this.element.innerHTML = this.text;
      if (this.onComplete) {
        this.onComplete();
      }
    }
  }

  Drupal.behaviors.aiResponseAnimation = {
    attach: function (context, settings) {
      if (!Drupal.behaviors.aiResponseAnimation.activeAnimations) {
        Drupal.behaviors.aiResponseAnimation.activeAnimations = new Map();
      }

      if (!Drupal.behaviors.aiResponseAnimation.metaContent) {
        Drupal.behaviors.aiResponseAnimation.metaContent = new Map();
      }

      function clearAllContent() {
        // Clear main response divs
        ['#chatgpt-response', '#claude-response', '#gemini-response'].forEach(selector => {
          const element = document.querySelector(selector);
          if (element) element.innerHTML = '';
        });

        // Clear meta divs
        ['#chatgpt-meta', '#claude-meta', '#gemini-meta'].forEach(selector => {
          const element = document.querySelector(selector);
          if (element) element.innerHTML = '';
        });

        // Clear stored meta content
        Drupal.behaviors.aiResponseAnimation.metaContent.clear();

        // Stop any active animations
        Drupal.behaviors.aiResponseAnimation.activeAnimations.forEach(animation => {
          animation.stop();
        });
        Drupal.behaviors.aiResponseAnimation.activeAnimations.clear();
      }

      function animateMetaInfo(metaSelector, metaText) {
        const metaElement = document.querySelector(metaSelector);
        if (!metaElement) return;
        
        const metaAnimation = new TypewriterAnimation(metaElement, metaText, 20);
        metaAnimation.start();
      }

      function animateResponse(selector, text, metaSelector) {
        const element = document.querySelector(selector);
        if (!element) return;

        // Stop existing animation if any
        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        // Create and start new animation with callback for meta information
        const animation = new TypewriterAnimation(element, text, 30);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);
        
        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
        });
      }

      // Listen for form submission
      const form = document.querySelector('form.drupal-ai-block-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          clearAllContent();
        });
      }

      // Override the existing HtmlCommand
      if (typeof Drupal.AjaxCommands.prototype.original_insert === 'undefined') {
        Drupal.AjaxCommands.prototype.original_insert = Drupal.AjaxCommands.prototype.insert;
        
        Drupal.AjaxCommands.prototype.insert = function (ajax, response, status) {
          const tempDiv = document.createElement('div');
          tempDiv.innerHTML = response.data;
          const text = tempDiv.textContent || tempDiv.innerText;

          // Map response selectors to their corresponding meta selectors
          const selectorPairs = {
            '#chatgpt-response': '#chatgpt-meta',
            '#claude-response': '#claude-meta',
            '#gemini-response': '#gemini-meta'
          };

          // If this is the first response in a new set, clear previous content
          if (response.selector === '#chatgpt-response') {
            clearAllContent();
          }

          // If this is a meta update, store it instead of inserting
          if (response.selector.endsWith('-meta')) {
            Drupal.behaviors.aiResponseAnimation.metaContent.set(response.selector, text);
            return; // Don't proceed with original insert
          }

          // For main responses, proceed with animation
          this.original_insert(ajax, response, status);

          if (selectorPairs[response.selector]) {
            animateResponse(
              response.selector,
              text,
              selectorPairs[response.selector]
            );
          }
        };
      }
    }
  };

})(jQuery, Drupal);