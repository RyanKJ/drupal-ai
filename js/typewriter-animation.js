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
      }, this.speed * 1.583);
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

      function animateMetaInfo(metaSelector, metaText) {
        const metaElement = document.querySelector(metaSelector);
        if (!metaElement) return;

        const metaAnimation = new TypewriterAnimation(metaElement, metaText, 20);
        metaAnimation.start();
      }

      function animateResponse(selector, text, metaSelector, metaText) {
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
          // Once main content animation is complete, animate the meta information
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
        });
      }

      // Override the existing HtmlCommand to include animation
      if (typeof Drupal.AjaxCommands.prototype.original_insert === 'undefined') {
        Drupal.AjaxCommands.prototype.original_insert = Drupal.AjaxCommands.prototype.insert;
        
        Drupal.AjaxCommands.prototype.insert = function (ajax, response, status) {
          // Extract the text content from the HTML
          const tempDiv = document.createElement('div');
          tempDiv.innerHTML = response.data;
          const text = tempDiv.textContent || tempDiv.innerText;

          // Call the original insert command first
          this.original_insert(ajax, response, status);

          // Map response selectors to their corresponding meta selectors
          const selectorPairs = {
            '#chatgpt-response': '#chatgpt-meta',
            '#claude-response': '#claude-meta',
            '#gemini-response': '#gemini-meta'
          };

          // If this is a main response, animate it and queue up its meta information
          if (selectorPairs[response.selector]) {
            animateResponse(
              response.selector,
              text,
              selectorPairs[response.selector],
              document.querySelector(selectorPairs[response.selector])?.getAttribute('data-meta-text')
            );
          }
          
          // If this is a meta update, store it for when the main content finishes
          if (response.selector.endsWith('-meta')) {
            const element = document.querySelector(response.selector);
            if (element) {
              element.setAttribute('data-meta-text', text);
            }
          }
        };
      }
    }
  };

})(jQuery, Drupal);