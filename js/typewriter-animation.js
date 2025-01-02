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