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

      const extractTextNodes = (node, isMeta = false) => {
        if (node.nodeType === Node.TEXT_NODE) {
          const text = node.textContent.trim();
          if (text) {
            const words = text.split(' ').filter(word => word.length > 0);
            words.forEach((word, index) => {
              this.words.push({ 
                text: word, 
                tag: null,
                isLastWord: index === words.length - 1
              });
            });
          }
        } else if (node.nodeType === Node.ELEMENT_NODE) {
          const tag = node.tagName.toLowerCase();
          if (['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag)) {
            this.words.push({ text: '', tag: tag, isBlockStart: true });
          }
          
          const children = Array.from(node.childNodes);
          children.forEach((child, index) => {
            if (child.nodeType === Node.TEXT_NODE) {
              const text = child.textContent.trim();
              if (text) {
                const words = text.split(' ').filter(word => word.length > 0);
                words.forEach((word, wordIndex) => {
                  this.words.push({ 
                    text: word, 
                    tag: tag,
                    isLastWord: index === children.length - 1 && wordIndex === words.length - 1
                  });
                });
              }
            } else {
              extractTextNodes(child, isMeta);
            }
          });
          
          // Only add br tag if it's not at the end of the content
          if (['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag) && !isMeta) {
            const isLastElement = this.isLastElement(node);
            if (!isLastElement || tag === 'br') {
              this.words.push({ text: '', tag: 'br', isLastBr: isLastElement });
            }
          }
        }
      };

      extractTextNodes(tempDiv);
      this.currentWord = 0;
      this.currentContainer = null;

      // Remove trailing br tags
      while (this.words.length > 0 && 
             this.words[this.words.length - 1].tag === 'br' && 
             this.words[this.words.length - 1].isLastBr) {
        this.words.pop();
      }
    }

    isLastElement(node) {
      let current = node;
      while (current.nextSibling) {
        if (current.nextSibling.nodeType === Node.ELEMENT_NODE || 
            (current.nextSibling.nodeType === Node.TEXT_NODE && current.nextSibling.textContent.trim())) {
          return false;
        }
        current = current.nextSibling;
      }
      return true;
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

      if (word.isBlockStart) {
        if (!this.currentContainer) {
          this.currentContainer = document.createElement(word.tag);
          this.element.appendChild(this.currentContainer);
        }
        this.currentWord++;
        this.animateWords();
        return;
      }

      let element;
      let targetContainer = this.currentContainer || this.element;

      if (word.tag === 'br') {
        if (!word.isLastBr) {
          this.currentContainer = null;
          let brElement = document.createElement('br');
          this.element.appendChild(brElement);
        }
        this.currentWord++;
        this.animateWords();
        return;
      }

      const wrapper = document.createDocumentFragment();
      const textNode = document.createTextNode(word.text + (!word.isLastWord ? ' ' : ''));

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
      
      setTimeout(() => {
        if (element && element.style) {
          element.style.opacity = '1';
        }
        this.currentWord++;
        this.animateWords();
      }, this.speed * 1.53);
    }

    stop() {
      this.isAnimating = false;
      this.currentWord = this.words.length;

      const tempDiv = document.createElement('div');
      let currentBlock = null;

      this.words.forEach((word) => {
        if (word.isBlockStart) {
          currentBlock = document.createElement(word.tag);
          tempDiv.appendChild(currentBlock);
        } else if (word.tag === 'br' && !word.isLastBr) {
          currentBlock = null;
          tempDiv.appendChild(document.createElement('br'));
        } else {
          const container = currentBlock || tempDiv;
          if (word.tag && !['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(word.tag)) {
            const element = document.createElement(word.tag);
            element.textContent = word.text + (!word.isLastWord ? ' ' : '');
            container.appendChild(element);
          } else {
            container.appendChild(document.createTextNode(word.text + (!word.isLastWord ? ' ' : '')));
          }
        }
      });

      this.element.innerHTML = tempDiv.innerHTML;

      if (this.onComplete) {
        this.onComplete();
      }
    }
  }

  // Main Drupal behavior
  Drupal.behaviors.aiResponseAnimation = {
    attach: function (context, settings) {
      if (!Drupal.behaviors.aiResponseAnimation.activeAnimations) {
        Drupal.behaviors.aiResponseAnimation.activeAnimations = new Map();
      }

      if (!Drupal.behaviors.aiResponseAnimation.metaContent) {
        Drupal.behaviors.aiResponseAnimation.metaContent = new Map();
      }

      // Store responses for consolidation
      if (!Drupal.behaviors.aiResponseAnimation.responses) {
        Drupal.behaviors.aiResponseAnimation.responses = {
          chatgpt: null,
          claude: null,
          gemini: null
        };
      }

      // Create a single consolidated aria container for screen readers
      function ensureConsolidatedAriaContainer() {
        const ariaId = 'consolidated-aria';
        if (!document.getElementById(ariaId)) {
          const container = document.createElement('div');
          container.id = ariaId;
          container.className = 'sr-only';
          container.setAttribute('aria-live', 'polite');
          container.style.position = 'absolute';
          container.style.width = '1px';
          container.style.height = '1px';
          container.style.padding = '0';
          container.style.margin = '-1px';
          container.style.overflow = 'hidden';
          container.style.clip = 'rect(0, 0, 0, 0)';
          container.style.whiteSpace = 'nowrap';
          container.style.border = '0';
          document.body.appendChild(container);
        }
      }

      // Update the consolidated ARIA content with all available responses
      function updateConsolidatedAriaContent() {
        const ariaContainer = document.getElementById('consolidated-aria');
        if (!ariaContainer) return;
        
        const responses = Drupal.behaviors.aiResponseAnimation.responses;
        
        // Only update if at least one response is available
        if (!responses.chatgpt && !responses.claude && !responses.gemini) return;
        
        let ariaContent = "Successful query, here are the following responses.\n";
        
        if (responses.chatgpt) {
          ariaContent += "ChatGPT response:\n" + responses.chatgpt + "\n\n";
        }
        
        if (responses.claude) {
          ariaContent += "Claude response:\n" + responses.claude + "\n\n";
        }
        
        if (responses.gemini) {
          ariaContent += "Gemini response:\n" + responses.gemini + "\n\n";
        }
        
        ariaContainer.textContent = ariaContent;
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

        // Clear the consolidated ARIA container
        const ariaContainer = document.getElementById('consolidated-aria');
        if (ariaContainer) ariaContainer.textContent = '';

        // Clear stored responses
        Drupal.behaviors.aiResponseAnimation.responses = {
          chatgpt: null,
          claude: null,
          gemini: null
        };

        Drupal.behaviors.aiResponseAnimation.metaContent.clear();

        Drupal.behaviors.aiResponseAnimation.activeAnimations.forEach(animation => {
          animation.stop();
        });
        Drupal.behaviors.aiResponseAnimation.activeAnimations.clear();
      }

      function animateMetaInfo(metaSelector, metaText) {
        const metaElement = document.querySelector(metaSelector);
        if (!metaElement) return;
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = metaText;
        let words = [];
        
        const extractMetaTextNodes = (node) => {
          if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent.trim();
            if (text) {
              const wordsArray = text.split(' ').filter(word => word.length > 0);
              wordsArray.forEach((word, index) => {
                words.push({ 
                  text: word, 
                  isLastWord: index === wordsArray.length - 1 
                });
              });
            }
          } else if (node.nodeType === Node.ELEMENT_NODE) {
            node.childNodes.forEach(child => {
              extractMetaTextNodes(child);
            });
          }
        }
        
        extractMetaTextNodes(tempDiv);
      
        metaElement.innerHTML = '';
        let currentWordIndex = 0;
        
        const animateMetaWords = () => {
          if (currentWordIndex >= words.length) {
            return;
          }

          const word = words[currentWordIndex];
          
          const wrapper = document.createDocumentFragment();
          const textNode = document.createTextNode(word.text + (!word.isLastWord ? ' ' : ''));

          const element = document.createElement('span');
          element.appendChild(textNode);
          element.style.opacity = '0';
          element.style.display = 'inline';
          wrapper.appendChild(element);
          
          metaElement.appendChild(wrapper);
          
          setTimeout(() => {
            if (element && element.style) {
              element.style.opacity = '1';
            }
            currentWordIndex++;
            animateMetaWords();
          }, 20 * 1.53);
        }
      
        animateMetaWords();
      }

      function animateResponse(selector, html, metaSelector, responseText) {
        const element = document.querySelector(selector);
        if (!element) return;

        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        // Store the plain text response for ARIA
        if (selector === '#chatgpt-response') {
          Drupal.behaviors.aiResponseAnimation.responses.chatgpt = responseText;
        } else if (selector === '#claude-response') {
          Drupal.behaviors.aiResponseAnimation.responses.claude = responseText;
        } else if (selector === '#gemini-response') {
          Drupal.behaviors.aiResponseAnimation.responses.gemini = responseText;
        }
        
        // Update the consolidated ARIA content
        updateConsolidatedAriaContent();

        const animation = new HTMLTypewriterAnimation(element, html, 30);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);

        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
        });
      }

      // Ensure consolidated aria container exists when the page loads
      ensureConsolidatedAriaContainer();

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
            // Extract plain text from the response data
            let responseText = null;
            
            if (response.data) {
              const tempDiv = document.createElement('div');
              tempDiv.innerHTML = response.data;
              
              // Try to get data-aria-text from the first child element
              const firstChild = tempDiv.querySelector('*');
              if (firstChild && firstChild.hasAttribute('data-aria-text')) {
                responseText = firstChild.getAttribute('data-aria-text');
              } else {
                // If no data-aria-text attribute, use the plain text content
                responseText = tempDiv.textContent.trim();
              }
            }
            
            animateResponse(
              response.selector,
              response.data,
              selectorPairs[response.selector],
              responseText
            );
          }
        };
      }
    }
  };
})(jQuery, Drupal);