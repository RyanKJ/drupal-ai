class HTMLTypewriterAnimation {
    constructor(element, html, speed = 30) {
      this.element = element;
      this.speed = speed;
      this.isAnimating = false;
      this.onComplete = null;

      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = html;
      this.words = [];

        const extractTextNodes = (node, currentTag = null) => {
          if (node.nodeType === Node.TEXT_NODE) {
            const words = node.textContent.split(' ').filter(word => word.length > 0);
            words.forEach(word => this.words.push({ text: word, tag: currentTag }));
          } else if (node.nodeType === Node.ELEMENT_NODE) {
            const tag = node.tagName.toLowerCase();
            if (['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tag)) {
              this.words.push({text: '', tag: tag, isBlockStart: true });
              node.childNodes.forEach(child => extractTextNodes(child, tag));
               this.words.push({text: '', tag: 'br'});
            } else {
              node.childNodes.forEach(child => extractTextNodes(child, tag));
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
        this.currentContainer = null;
    }
    else {
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