(function ($, Drupal) {
  'use strict';

  class HTMLTypewriterAnimation {
    constructor(element, html, speed = 30, ariaText = null) {
      this.element = element;
      this.speed = speed;
      this.isAnimating = false;
      this.onComplete = null;
      this.ariaText = ariaText; // Store the aria-text for screen readers

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
      
      // Add the aria-text to the corresponding aria container if provided
      if (this.ariaText) {
        const elementId = this.element.id;
        const modelName = elementId.replace('-response', '');
        const ariaId = `${modelName}-aria`;
        const ariaContainer = document.getElementById(ariaId);
        
        if (ariaContainer) {
          // Clear previous content
          ariaContainer.innerHTML = '';
          
          // Create a new element with a unique ID for this announcement
          const announcement = document.createElement('div');
          announcement.id = `${ariaId}-announcement-${Date.now()}`;
          announcement.setAttribute('aria-live', 'polite');
          
          // Add model name prefix to the text
          const modelLabel = modelName.charAt(0).toUpperCase() + modelName.slice(1);
          announcement.textContent = `${modelLabel} responds: ${this.ariaText}`;
          
          ariaContainer.appendChild(announcement);
          
          // Set a focus management technique - update the tabindex
          const responseElement = document.getElementById(elementId);
          if (responseElement && !responseElement.hasAttribute('tabindex')) {
            responseElement.setAttribute('tabindex', '-1');
          }
        }
      }
      
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
      
      // Track response order for screen reader announcements
      if (!Drupal.behaviors.aiResponseAnimation.responseQueue) {
        Drupal.behaviors.aiResponseAnimation.responseQueue = [];
      }

      // Create aria containers for screen readers if they don't exist
      function ensureAriaContainers() {
        const ariaContainerWrapper = document.getElementById('aria-container-wrapper');
        
        if (!ariaContainerWrapper) {
          const wrapper = document.createElement('div');
          wrapper.id = 'aria-container-wrapper';
          wrapper.setAttribute('role', 'status');
          wrapper.style.position = 'absolute';
          wrapper.style.width = '1px';
          wrapper.style.height = '1px';
          wrapper.style.padding = '0';
          wrapper.style.margin = '-1px';
          wrapper.style.overflow = 'hidden';
          wrapper.style.clip = 'rect(0, 0, 0, 0)';
          wrapper.style.whiteSpace = 'nowrap';
          wrapper.style.border = '0';
          document.body.appendChild(wrapper);
        }
        
        ['chatgpt', 'claude', 'gemini'].forEach(model => {
          const ariaId = `${model}-aria`;
          if (!document.getElementById(ariaId)) {
            const container = document.createElement('div');
            container.id = ariaId;
            container.className = 'sr-only';
            // Don't set aria-live here, we'll set it on individual announcements
            container.style.position = 'relative';
            document.getElementById('aria-container-wrapper').appendChild(container);
            
            // Add corresponding visible headers for each section (visually hidden but accessible)
            const responseElement = document.getElementById(`${model}-response`);
            if (responseElement) {
              // Check if heading already exists
              if (!responseElement.previousElementSibling || 
                  !responseElement.previousElementSibling.classList.contains('ai-response-heading')) {
                const heading = document.createElement('h3');
                heading.classList.add('ai-response-heading');
                heading.style.fontSize = '1rem';
                heading.style.marginTop = '1rem';
                heading.style.marginBottom = '0.5rem';
                heading.textContent = `${model.charAt(0).toUpperCase() + model.slice(1)} Response`;
                
                const parent = responseElement.parentNode;
                parent.insertBefore(heading, responseElement);
              }
            }
          }
        });
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

        // Clear aria containers
        ['chatgpt-aria', 'claude-aria', 'gemini-aria'].forEach(ariaId => {
          const element = document.getElementById(ariaId);
          if (element) element.innerHTML = '';
        });

        // Reset the response queue
        Drupal.behaviors.aiResponseAnimation.responseQueue = [];
        
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

      function processResponseQueue() {
        // If no responses or already processing, exit
        if (Drupal.behaviors.aiResponseAnimation.responseQueue.length === 0 ||
            Drupal.behaviors.aiResponseAnimation.processingQueue) {
          return;
        }
        
        Drupal.behaviors.aiResponseAnimation.processingQueue = true;
        
        // Get the next response in the queue
        const nextResponse = Drupal.behaviors.aiResponseAnimation.responseQueue.shift();
        const { selector, html, metaSelector, ariaText } = nextResponse;
        
        // Start animation
        const element = document.querySelector(selector);
        if (!element) {
          Drupal.behaviors.aiResponseAnimation.processingQueue = false;
          processResponseQueue();
          return;
        }

        const existingAnimation = Drupal.behaviors.aiResponseAnimation.activeAnimations.get(selector);
        if (existingAnimation) {
          existingAnimation.stop();
        }

        const animation = new HTMLTypewriterAnimation(element, html, 30, ariaText);
        Drupal.behaviors.aiResponseAnimation.activeAnimations.set(selector, animation);

        animation.start(() => {
          const metaText = Drupal.behaviors.aiResponseAnimation.metaContent.get(metaSelector);
          if (metaSelector && metaText) {
            animateMetaInfo(metaSelector, metaText);
          }
          
          // Animation completed, process next in queue
          Drupal.behaviors.aiResponseAnimation.processingQueue = false;
          processResponseQueue();
        });
      }

      function animateResponse(selector, html, metaSelector, ariaText = null) {
        // Add response to queue
        Drupal.behaviors.aiResponseAnimation.responseQueue.push({
          selector, html, metaSelector, ariaText
        });
        
        // Start processing if not already
        if (!Drupal.behaviors.aiResponseAnimation.processingQueue) {
          processResponseQueue();
        }
      }

      // Ensure aria containers exist when the page loads
      ensureAriaContainers();

      const form = document.querySelector('form.drupal-ai-block-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          clearAllContent();
          
          // Set focus trap for screen readers
          const focusTrap = document.createElement('div');
          focusTrap.setAttribute('tabindex', '-1');
          focusTrap.setAttribute('aria-label', 'Processing AI responses');
          document.body.appendChild(focusTrap);
          focusTrap.focus();
          
          // Remove focus trap after a short delay
          setTimeout(() => {
            if (focusTrap && focusTrap.parentNode) {
              focusTrap.parentNode.removeChild(focusTrap);
            }
          }, 500);
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

          // The first response that comes back from Ajax, reset all content
          if (response.selector === '#chatgpt-response') {
            clearAllContent();
          }

          if (response.selector.endsWith('-meta')) {
            Drupal.behaviors.aiResponseAnimation.metaContent.set(response.selector, response.data);
            return;
          }

          this.original_insert(ajax, response, status);

          if (selectorPairs[response.selector]) {
            // Extract ariaText from the response data if it exists
            let ariaText = null;
            
            // Check for aria-text attribute in the response
            if (response.data) {
              const tempDiv = document.createElement('div');
              tempDiv.innerHTML = response.data;
              
              // Try to get aria-text from the first child element
              const firstChild = tempDiv.querySelector('*');
              if (firstChild && firstChild.hasAttribute('data-aria-text')) {
                ariaText = firstChild.getAttribute('data-aria-text');
              } else {
                // If no aria-text attribute, use the plain text content
                ariaText = tempDiv.textContent.trim();
              }
            }
            
            animateResponse(
              response.selector,
              response.data,
              selectorPairs[response.selector],
              ariaText
            );
          }
        };
      }
    }
  };
})(jQuery, Drupal);