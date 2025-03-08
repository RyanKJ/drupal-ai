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
          gemini: null,
          isAllResponsesReceived: false,
          lastUpdated: 0
        };
      }

      // Create a single consolidated aria container for screen readers
      function ensureConsolidatedAriaContainer() {
        const ariaId = 'consolidated-aria';
        if (!document.getElementById(ariaId)) {
          const container = document.createElement('div');
          container.id = ariaId;
          container.className = 'sr-only';
          // Using 'assertive' to ensure screen readers announce the update
          container.setAttribute('aria-live', 'assertive');
          container.setAttribute('aria-atomic', 'true');
          container.setAttribute('role', 'status');
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
        return document.getElementById(ariaId);
      }

      // Check if all expected responses have been received
      function areAllResponsesReceived() {
        const responses = Drupal.behaviors.aiResponseAnimation.responses;
        return responses.chatgpt && responses.claude && responses.gemini;
      }

      // Update the consolidated ARIA content with all available responses
      function updateConsolidatedAriaContent(forceUpdate = false) {
        const responses = Drupal.behaviors.aiResponseAnimation.responses;
        const now = Date.now();
        
        // Prevent rapid successive updates (debounce)
        if (!forceUpdate && now - responses.lastUpdated < 500) {
          return;
        }
        
        // Check if we should wait for more responses
        if (!forceUpdate && !responses.isAllResponsesReceived && !areAllResponsesReceived()) {
          // If not all responses are in yet, and this isn't a forced update, wait
          return;
        }
        
        // If all responses are received for the first time, mark it
        if (!responses.isAllResponsesReceived && areAllResponsesReceived()) {
          responses.isAllResponsesReceived = true;
        }
        
        responses.lastUpdated = now;
        
        // Get or create the aria container
        const ariaContainer = ensureConsolidatedAriaContainer();
        
        // Create a new element for the update to ensure screen readers detect the change
        const newAriaContent = document.createElement('div');
        
        let contentText = "Successful query, here are the following responses.\n";
        
        if (responses.chatgpt) {
          contentText += "ChatGPT response:\n" + responses.chatgpt + "\n\n";
        }
        
        if (responses.claude) {
          contentText += "Claude response:\n" + responses.claude + "\n\n";
        }
        
        if (responses.gemini) {
          contentText += "Gemini response:\n" + responses.gemini + "\n\n";
        }
        
        newAriaContent.textContent = contentText;
        
        // Clear existing content and add the new content
        ariaContainer.innerHTML = '';
        ariaContainer.appendChild(newAriaContent);
      }

      // Reliable way to update ARIA when all responses are in
      function scheduleCompleteAriaUpdate() {
        // Reset the flag that tracks if all responses were received
        Drupal.behaviors.aiResponseAnimation.responses.isAllResponsesReceived = false;
        
        // Check every 500ms if all responses have been received
        const checkInterval = setInterval(() => {
          if (areAllResponsesReceived()) {
            // Force a final update when all responses are in
            updateConsolidatedAriaContent(true);
            clearInterval(checkInterval);
          }
        }, 500);
        
        // Safety timeout to prevent indefinite waiting
        setTimeout(() => {
          clearInterval(checkInterval);
          // Force a final update even if not all responses came in
          updateConsolidatedAriaContent(true);
        }, 10000); // 10-second maximum wait
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
          gemini: null,
          isAllResponsesReceived: false,
          lastUpdated: 0
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
          // Start checking for all responses if this is the first one
          if (!Drupal.behaviors.aiResponseAnimation.responses.claude && 
              !Drupal.behaviors.aiResponseAnimation.responses.gemini) {
            scheduleCompleteAriaUpdate();
          }
        } else if (selector === '#claude-response') {
          Drupal.behaviors.aiResponseAnimation.responses.claude = responseText;
        } else if (selector === '#gemini-response') {
          Drupal.behaviors.aiResponseAnimation.responses.gemini = responseText;
        }
        
        // Update the consolidated ARIA content (will be debounced internally)
        updateConsolidatedAriaContent();

        const animation = new HTMLTypewriterAnimation(element, html, 30);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);

        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
          
          // Check if this was the last animation to complete
          const isLastResponse = 
            (selector === '#gemini-response' && 
             Drupal.behaviors.aiResponseAnimation.responses.chatgpt && 
             Drupal.behaviors.aiResponseAnimation.responses.claude) ||
            (selector === '#claude-response' && 
             !Drupal.behaviors.aiResponseAnimation.responses.gemini && 
             Drupal.behaviors.aiResponseAnimation.responses.chatgpt);
             
          if (isLastResponse) {
            // Force an update when the last animation completes
            setTimeout(() => {
              updateConsolidatedAriaContent(true);
            }, 500);
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