(function ($, Drupal) {
  'use strict';

  class HTMLTypewriterAnimation {
    constructor(element, html, speed = 30) {
      this.element = element;
      this.speed = speed;
      this.isAnimating = false;
      this.onComplete = null;
      
      // Parse HTML into elements/words while preserving formatting
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = html;
      this.words = [];
      
      const extractTextNodes = (node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          // Split text nodes into words
          const words = node.textContent.split(' ').filter(word => word.length > 0);
          words.forEach(word => this.words.push({ text: word, tag: null }));
        } else if (node.nodeType === Node.ELEMENT_NODE) {
          // For element nodes, wrap words in their original tags
          const tag = node.tagName.toLowerCase();
          node.childNodes.forEach(child => {
            if (child.nodeType === Node.TEXT_NODE) {
              const words = child.textContent.split(' ').filter(word => word.length > 0);
              words.forEach(word => this.words.push({ text: word, tag: tag }));
            } else {
              extractTextNodes(child);
            }
          });
          
          // Add an extra space after block elements
          if (['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li'].includes(tag)) {
            this.words.push({ text: '', tag: 'br' });
          }
        }
      };
      
      extractTextNodes(tempDiv);
      this.currentWord = 0;
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
      let element;
      
      if (word.tag === 'br') {
        element = document.createElement('br');
      } else {
        element = word.tag ? document.createElement(word.tag) : document.createElement('span');
        element.textContent = word.text + ' ';
        element.style.opacity = '0';
      }
      
      this.element.appendChild(element);

      setTimeout(() => {
        if (element.style) {
          element.style.opacity = '1';
        }
        this.currentWord++;
        this.animateWords();
      }, this.speed * 1.53);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;
      // Reconstruct original HTML
      const tempDiv = document.createElement('div');
      this.words.forEach(word => {
        if (word.tag === 'br') {
          tempDiv.appendChild(document.createElement('br'));
        } else {
          const element = word.tag ? document.createElement(word.tag) : document.createTextNode(word.text + ' ');
          if (word.tag) {
            element.textContent = word.text + ' ';
          }
          tempDiv.appendChild(element);
        }
      });
      this.element.innerHTML = tempDiv.innerHTML;
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
        
        const metaAnimation = new HTMLTypewriterAnimation(metaElement, metaText, 20);
        metaAnimation.start();
      }

      function animateResponse(selector, html, metaSelector) {
        const element = document.querySelector(selector);
        if (!element) return;

        // Stop existing animation if any
        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        // Create and start new animation with callback for meta information
        const animation = new HTMLTypewriterAnimation(element, html, 30);
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
            Drupal.behaviors.aiResponseAnimation.metaContent.set(response.selector, response.data);
            return; // Don't proceed with original insert
          }

          // For main responses, proceed with animation
          this.original_insert(ajax, response, status);

          if (selectorPairs[response.selector]) {
            animateResponse(
              response.selector,
              response.data,
              selectorPairs[response.selector]
            );
          }
        };
      }
    }
  };

})(jQuery, Drupal);