(function ($, Drupal) {
  'use strict';

  class HTMLTypewriterAnimation {
    constructor(element, html, speed = 30) {
      this.element = element;
      this.speed = speed;
      this.isAnimating = false;
      this.onComplete = null;

      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = html;
      this.words = [];

      const extractTextNodes = (node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          const words = node.textContent.split(' ').filter(word => word.length > 0);
          words.forEach(word => this.words.push({ text: word, tag: null }));
        } else if (node.nodeType === Node.ELEMENT_NODE) {
          const tag = node.tagName.toLowerCase();
          if (['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag)) {
            this.words.push({ text: '', tag: tag, isBlockStart: true });
          }

          node.childNodes.forEach(child => {
            if (child.nodeType === Node.TEXT_NODE) {
              const words = child.textContent.split(' ').filter(word => word.length > 0);
              words.forEach(word => this.words.push({ text: word, tag: tag }));
            } else {
              extractTextNodes(child);
            }
          });

          if (['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag)) {
            this.words.push({ text: '', tag: 'br' });
          }
        }
      };

      extractTextNodes(tempDiv);
      this.currentWord = 0;

      // Initialize container for block elements
      this.currentContainer = null;
    }

    start(onCompleteCallback) {
      if (this.isAnimating) return;
      this.isAnimating = true;
      this.onComplete = onCompleteCallback;
      this.element.innerHTML = '';
      this.currentContainer = null;
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

      // Handle block elements
        if (word.isBlockStart) {
          // Only create a new container if one doesn't exist
        if (!this.currentContainer) {
            this.currentContainer = document.createElement(word.tag);
            this.element.appendChild(this.currentContainer);
        }

        // Move to the next word and continue animation
        this.currentWord++;
        this.animateWords();
        return;
      }

      let element;
      let targetContainer = this.currentContainer || this.element;

      if (word.tag === 'br') {
          this.currentContainer = null;
          // Defer adding BR element until later
      } else {
          const wrapper = document.createDocumentFragment();
          const textNode = document.createTextNode(word.text + ' ');

          if (word.tag && !['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(word.tag)) {
              element = document.createElement(word.tag);
              element.appendChild(textNode);
              element.style.opacity = '0';
              wrapper.appendChild(element);
          } else {
              element = document.createElement('span');
              element.appendChild(textNode);
              element.style.opacity = '0';
              element.style.display = 'inline';
              wrapper.appendChild(element);
          }

          targetContainer.appendChild(wrapper);

          if (this.currentContainer && this.currentContainer.style.opacity !== '1') {
              this.currentContainer.style.opacity = '1';
          }
      }
      
      setTimeout(() => {
        if (element && element.style) {
            element.style.opacity = '1';
        }
        
        if (this.words[this.currentWord] && this.words[this.currentWord].tag === 'br'){
          let brElement = document.createElement('br');
          this.element.appendChild(brElement);
          this.currentWord++;
          this.animateWords();
        } else {
          this.currentWord++;
          this.animateWords();
        }
          
      }, this.speed * 1.53);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;

      const tempDiv = document.createElement('div');
      let currentBlock = null;

      this.words.forEach(word => {
        if (word.isBlockStart) {
          currentBlock = document.createElement(word.tag);
          tempDiv.appendChild(currentBlock);
        } else if (word.tag === 'br') {
          currentBlock = null;
          tempDiv.appendChild(document.createElement('br'));
        } else {
          const container = currentBlock || tempDiv;
          if (word.tag && !['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(word.tag)) {
            const element = document.createElement(word.tag);
            element.textContent = word.text + ' ';
            container.appendChild(element);
          } else {
            container.appendChild(document.createTextNode(word.text + ' '));
          }
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
        ['#chatgpt-response', '#claude-response', '#gemini-response'].forEach(selector => {
          const element = document.querySelector(selector);
          if (element) element.innerHTML = '';
        });

        ['#chatgpt-meta', '#claude-meta', '#gemini-meta'].forEach(selector => {
          const element = document.querySelector(selector);
          if (element) element.innerHTML = '';
        });

        Drupal.behaviors.aiResponseAnimation.metaContent.clear();

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

        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        const animation = new HTMLTypewriterAnimation(element, html, 30);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);

        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
        });
      }

      const form = document.querySelector('form.drupal-ai-block-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          clearAllContent();
        });
      }

      if (typeof Drupal.AjaxCommands.prototype.original_insert === 'undefined') {
        Drupal.AjaxCommands.prototype.original_insert = Drupal.AjaxCommands.prototype.insert;

        Drupal.AjaxCommands.prototype.insert = function (ajax, response, status) {
          const selectorPairs = {
            '#chatgpt-response': '#chatgpt-meta',
            '#claude-response': '#claude-meta',
            '#gemini-response': '#gemini-meta'
          };

          if (response.selector === '#chatgpt-response') {
            clearAllContent();
          }

          if (response.selector.endsWith('-meta')) {
            Drupal.behaviors.aiResponseAnimation.metaContent.set(response.selector, response.data);
            return;
          }

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