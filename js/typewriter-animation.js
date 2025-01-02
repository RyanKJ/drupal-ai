(function ($, Drupal) {
  'use strict';

  class TypewriterAnimation {
    constructor(element, text, speed = 30) {
      this.element = element;
      this.text = text;
      this.speed = speed;
      this.currentChar = 0;
      this.words = this.text.split(' ');
      this.currentWord = 0;
      this.isAnimating = false;
    }

    start() {
      if (this.isAnimating) return;
      this.isAnimating = true;
      this.element.innerHTML = '';
      this.animateWords();
    }

    animateWords() {
      if (this.currentWord >= this.words.length) {
        this.isAnimating = false;
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
      }, this.speed * 1.853);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;
      this.element.innerHTML = this.text;
    }
  }

  Drupal.behaviors.aiResponseAnimation = {
    attach: function (context, settings) {
      // Store active animations
      if (!Drupal.behaviors.aiResponseAnimation.activeAnimations) {
        Drupal.behaviors.aiResponseAnimation.activeAnimations = new Map();
      }

      // Function to start animation for a specific response
      function animateResponse(selector, text) {
        const element = document.querySelector(selector);
        if (!element) return;

        // Stop existing animation if any
        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        // Create and start new animation
        const animation = new TypewriterAnimation(element, text, 30);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);
        animation.start();
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

          // Start animation based on the target selector
          if (response.selector === '#chatgpt-response' || 
              response.selector === '#claude-response' || 
              response.selector === '#gemini-response') {
            animateResponse(response.selector, text);
          }
        };
      }
    }
  };

})(jQuery, Drupal);