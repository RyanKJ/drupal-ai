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
      this.id = Date.now();
    }

    start(onCompleteCallback) {
      if (this.isAnimating) return;
      this.isAnimating = true;
      this.onComplete = onCompleteCallback;
      this.element.innerHTML = '';
      this.animateWords();
    }

    animateWords() {
      if (!this.isAnimating || this.currentWord >= this.words.length) {
        const wasAnimating = this.isAnimating;
        this.isAnimating = false;
        
        // Call callback if we finished naturally (not stopped)
        if (wasAnimating && this.onComplete && this.currentWord >= this.words.length) {
          this.onComplete(this.id);
        }
        return;
      }

      const word = this.words[this.currentWord];
      const span = document.createElement('span');
      span.textContent = word + ' ';
      span.style.opacity = '0';
      this.element.appendChild(span);

      setTimeout(() => {
        if (this.isAnimating) {
          span.style.opacity = '1';
          this.currentWord++;
          this.animateWords();
        }
      }, this.speed * 1.53);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;
      this.element.innerHTML = this.text;
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

      // Add a property to track the current animation set
      if (!Drupal.behaviors.aiResponseAnimation.currentAnimationSet) {
        Drupal.behaviors.aiResponseAnimation.currentAnimationSet = Date.now();
      }

      function clearAllContent() {
        // Update the animation set ID to invalidate any pending callbacks
        Drupal.behaviors.aiResponseAnimation.currentAnimationSet = Date.now();

        // Define all selectors
        const allSelectors = {
          responses: ['#chatgpt-response', '#claude-response', '#gemini-response'],
          meta: ['#chatgpt-meta', '#claude-meta', '#gemini-meta']
        };

        // Clear main response divs
        allSelectors.responses.forEach(selector => {
          const element = document.querySelector(selector);
          if (element) {
            while (element.firstChild) {
              element.removeChild(element.firstChild);
            }
          }
        });

        // Clear meta divs - using direct text content removal
        allSelectors.meta.forEach(selector => {
          const element = document.querySelector(selector);
          if (element) {
            while (element.firstChild) {
              element.removeChild(element.firstChild);
            }
            element.textContent = ''; // Ensure complete clearing
          }
        });

        // Stop any active animations and clear them from tracking
        Drupal.behaviors.aiResponseAnimation.activeAnimations.forEach(animation => {
          if (animation && typeof animation.stop === 'function') {
            animation.stop();
          }
        });
        Drupal.behaviors.aiResponseAnimation.activeAnimations.clear();

        // Clear stored meta content
        Drupal.behaviors.aiResponseAnimation.metaContent.clear();
      }


      function animateMetaInfo(metaSelector, metaText, animationSetId) {
        // Only animate if this is still the current animation set
        if (animationSetId !== Drupal.behaviors.aiResponseAnimation.currentAnimationSet) {
          return;
        }

        const metaElement = document.querySelector(metaSelector);
        if (!metaElement) return;
        
        const metaAnimation = new TypewriterAnimation(metaElement, metaText, 20);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(metaSelector, metaAnimation);
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

        // Create and start new animation
        const animation = new TypewriterAnimation(element, text, 30);
        const currentAnimationSetId = Drupal.behaviors.aiResponseAnimation.currentAnimationSet;
        
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);
        
        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText, currentAnimationSetId);
          }
        });
      }
      
      // Ensure we're properly binding the form submit event
      const form = document.querySelector('form.drupal-ai-block-form');
      if (form && !form.hasAttribute('data-clear-listener')) {
        form.setAttribute('data-clear-listener', 'true');
        form.addEventListener('submit', function(e) {
          clearAllContent();
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