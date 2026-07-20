/**
 * Projects Directory Pro - Slider Script
 * Version: 30.0 - Fixed for Arabic & English
 * يضمن تشغيل السلايدر تلقائياً وتبديل الصور في كلا اللغتين
 * معدّل: عرض الصور بحجمها الأصلي بدون قص
 */

(function() {
    'use strict';

    // الإعدادات
    const CONFIG = {
        autoplay: true,
        interval: 4000,        // 4 ثواني بين كل صورة
        transitionSpeed: 500,  // سرعة الانتقال
        pauseOnHover: true,
        enableTouch: true,
        enableKeyboard: true,
        debug: false           // تفعيل للتشخيص
    };

    // قائمة الـ selectors الممكنة للسلايدر
    const SELECTORS = {
        slider: [
            '.pdp-card-slider',
            '.pdp-project-slider', 
            '.pdp-gallery-slider',
            '[data-slider="true"]',
            '[data-total]',
            '.pdp-slider'
        ],
        track: [
            '.pdp-slider-track',
            '.slider-track',
            '[data-slider-track]'
        ],
        slide: [
            '.pdp-slider-slide',
            '.pdp-slide',
            '.slider-slide',
            '[data-slide]'
        ],
        dots: [
            '.pdp-slider-dots',
            '.slider-dots'
        ],
        dot: [
            '.pdp-slider-dot',
            '.pdp-dot',
            '.slider-dot'
        ],
        prev: [
            '.pdp-slider-prev',
            '.pdp-prev-btn',
            '.slider-prev',
            '[data-slider-prev]'
        ],
        next: [
            '.pdp-slider-next',
            '.pdp-next-btn',
            '.slider-next',
            '[data-slider-next]'
        ],
        progress: [
            '.pdp-slider-progress',
            '.pdp-progress-fill',
            '.progress-fill'
        ],
        counter: [
            '.pdp-slide-current',
            '.pdp-current-slide',
            '.slider-counter-current'
        ]
    };

    /**
     * البحث عن عنصر باستخدام قائمة selectors
     */
    function findElement(parent, selectorList) {
        for (let i = 0; i < selectorList.length; i++) {
            const el = parent.querySelector(selectorList[i]);
            if (el) return el;
        }
        return null;
    }

    /**
     * البحث عن جميع العناصر باستخدام قائمة selectors
     */
    function findAllElements(parent, selectorList) {
        for (let i = 0; i < selectorList.length; i++) {
            const els = parent.querySelectorAll(selectorList[i]);
            if (els.length > 0) return els;
        }
        return [];
    }

    /**
     * حقن CSS للسلايدر - عرض الصور بحجمها الأصلي
     */
    function injectSliderStyles() {
        if (document.getElementById('pdp-slider-natural-styles')) return;
        
        const styleEl = document.createElement('style');
        styleEl.id = 'pdp-slider-natural-styles';
        styleEl.textContent = `
            /* ═══════════════════════════════════════════════════════════════ */
            /* السلايدر الرئيسي - عرض الصور بحجمها الطبيعي                      */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-card-slider,
            .pdp-project-slider,
            .pdp-gallery-slider,
            .pdp-slider {
                position: relative;
                overflow: hidden;
                width: 100%;
                background: linear-gradient(135deg, #1D2D51 0%, #2a3f6e 100%);
                border-radius: 12px 12px 0 0;
                min-height: 200px;
            }
            
            /* الـ Track - حاوية الشرائح */
            .pdp-slider-track,
            .slider-track {
                display: flex;
                transition: transform 500ms cubic-bezier(0.4, 0, 0.2, 1);
                will-change: transform;
                align-items: center;
                min-height: 200px;
            }
            
            /* الشريحة الواحدة */
            .pdp-slider-slide,
            .pdp-slide,
            .slider-slide {
                flex: 0 0 100%;
                width: 100%;
                min-width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px;
                box-sizing: border-box;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* الصورة - بحجمها الطبيعي بدون قص                                  */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-slider-slide img,
            .pdp-slide img,
            .slider-slide img,
            .pdp-card-slider img {
                /* إزالة object-fit: cover */
                object-fit: contain !important;
                
                /* السماح للصورة بالحفاظ على أبعادها */
                width: auto !important;
                height: auto !important;
                
                        /* الحد الأقصى للأبعاد */
                max-width: 100% !important;
                max-height: 280px !important;
                
                /* توسيط الصورة */
                display: block;
                margin: 0 auto;
                
                /* تنعيم الحواف */
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                
                /* خلفية للصور الشفافة */
                background: #f8f9fa;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* أزرار التنقل                                                     */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-slider-prev,
            .pdp-slider-next,
            .pdp-prev-btn,
            .pdp-next-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 36px;
                height: 36px;
                background: rgba(255, 255, 255, 0.95);
                border: none;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                transition: all 0.3s ease;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
            
            .pdp-slider-prev:hover,
            .pdp-slider-next:hover,
            .pdp-prev-btn:hover,
            .pdp-next-btn:hover {
                background: #C9A961;
                transform: translateY(-50%) scale(1.1);
            }
            
            .pdp-slider-prev,
            .pdp-prev-btn {
                left: 10px;
            }
            
            .pdp-slider-next,
            .pdp-next-btn {
                right: 10px;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* النقاط (Dots)                                                   */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-slider-dots,
            .slider-dots {
                position: absolute;
                bottom: 12px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                gap: 8px;
                z-index: 10;
                padding: 6px 12px;
                background: rgba(0, 0, 0, 0.3);
                border-radius: 20px;
            }
            
            .pdp-slider-dot,
            .pdp-dot,
            .slider-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                cursor: pointer;
                transition: all 0.3s ease;
                border: none;
                padding: 0;
            }
            
            .pdp-slider-dot:hover,
            .pdp-dot:hover,
            .slider-dot:hover {
                background: rgba(255, 255, 255, 0.8);
            }
            
            .pdp-slider-dot.active,
            .pdp-dot.active,
            .slider-dot.active {
                background: #C9A961;
                transform: scale(1.2);
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* العداد                                                          */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-slide-counter,
            .slider-counter {
                position: absolute;
                top: 12px;
                left: 12px;
                background: rgba(0, 0, 0, 0.6);
                color: #fff;
                padding: 4px 10px;
                border-radius: 15px;
                font-size: 13px;
                font-weight: 600;
                z-index: 10;
                direction: ltr;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* شريط التقدم                                                     */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-slider-progress-bar {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: rgba(255, 255, 255, 0.2);
                z-index: 10;
            }
            
            .pdp-slider-progress,
            .pdp-progress-fill,
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #C9A961, #e0c285);
                width: 0%;
                transition: width 50ms linear;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* شارة مشروع مميز                                                 */
            /* ═══════════════════════════════════════════════════════════════ */
            
            .pdp-featured-badge {
                position: absolute;
                top: 12px;
                right: 12px;
                z-index: 10;
            }
            
            .pdp-featured-badge img {
                width: 40px !important;
                height: 40px !important;
                max-height: 40px !important;
                object-fit: contain !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            
            /* ═══════════════════════════════════════════════════════════════ */
            /* التجاوب - الشاشات المختلفة                                      */
            /* ═══════════════════════════════════════════════════════════════ */
            
            @media (max-width: 768px) {
                .pdp-slider-slide img,
                .pdp-slide img,
                .slider-slide img,
                .pdp-card-slider img {
                    max-height: 220px !important;
                }
                
                .pdp-slider-prev,
                .pdp-slider-next,
                .pdp-prev-btn,
                .pdp-next-btn {
                    width: 32px;
                    height: 32px;
                }
                
                .pdp-slider-dots,
                .slider-dots {
                    padding: 4px 10px;
                    gap: 6px;
                }
                
                .pdp-slider-dot,
                .pdp-dot,
                .slider-dot {
                    width: 8px;
                    height: 8px;
                }
            }
            
            @media (max-width: 480px) {
                .pdp-slider-slide img,
                .pdp-slide img,
                .slider-slide img,
                .pdp-card-slider img {
                    max-height: 180px !important;
                }
                
                .pdp-slider-prev,
                .pdp-slider-next,
                .pdp-prev-btn,
                .pdp-next-btn {
                    width: 28px;
                    height: 28px;
                }
            }
        `;
        
        document.head.appendChild(styleEl);
    }

    /**
     * تهيئة عند تحميل الصفحة
     */
    function init() {
        // حقن الأنماط أولاً
        injectSliderStyles();
        
        // تهيئة فورية
        initSliders();
        
        // تهيئة بعد تحميل DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                injectSliderStyles();
                initSliders();
            });
        }
        
        // تهيئة بعد تحميل كل شيء
        window.addEventListener('load', function() {
            setTimeout(initSliders, 100);
            setTimeout(initSliders, 500);
        });
    }

    /**
     * تهيئة جميع السلايدرات
     */
    function initSliders() {
        // جمع كل السلايدرات من جميع الـ selectors
        let allSliders = [];
        
        SELECTORS.slider.forEach(function(selector) {
            const sliders = document.querySelectorAll(selector);
            sliders.forEach(function(slider) {
                if (!allSliders.includes(slider)) {
                    allSliders.push(slider);
                }
            });
        });

        // تهيئة كل سلايدر
        let initializedCount = 0;
        allSliders.forEach(function(slider, index) {
            if (!slider.dataset.pdpSliderInit) {
                slider.dataset.pdpSliderInit = 'true';
                new PDPSlider(slider, index);
                initializedCount++;
            }
        });

        if (CONFIG.debug && initializedCount > 0) {
            console.log('🎠 PDP Sliders: تم تهيئة ' + initializedCount + ' سلايدر جديد');
        }
    }

    /**
     * كلاس السلايدر الرئيسي
     */
    function PDPSlider(element, id) {
        this.slider = element;
        this.id = id;
        
        // البحث عن العناصر
        this.track = findElement(element, SELECTORS.track);
        this.slides = findAllElements(element, SELECTORS.slide);
        this.dotsContainer = findElement(element, SELECTORS.dots);
        this.prevBtn = findElement(element, SELECTORS.prev);
        this.nextBtn = findElement(element, SELECTORS.next);
        this.progressBar = findElement(element, SELECTORS.progress);
        this.counterEl = findElement(element, SELECTORS.counter);
        
        // الحالة
        this.currentIndex = 0;
        this.totalSlides = this.slides.length;
        this.autoplayTimer = null;
        this.progressTimer = null;
        this.isHovered = false;
        this.isPaused = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.progressValue = 0;

        // التحقق من وجود شرائح - إذا لم توجد، نبحث عن الصور
        if (this.totalSlides === 0) {
            const images = element.querySelectorAll('img');
            if (images.length > 0) {
                this.createSlidesFromImages(images);
            }
        }

        // التهيئة
        if (this.totalSlides > 0) {
            this.init();
            
            if (CONFIG.debug) {
                console.log('🎠 Slider #' + id + ': ' + this.totalSlides + ' شرائح');
            }
        }
    }

    /**
     * إنشاء شرائح من الصور - مهم لعرض كل الصور
     */
    PDPSlider.prototype.createSlidesFromImages = function(images) {
        const self = this;
        
        // إذا لم يوجد track، نبحث أو ننشئ واحد
        if (!this.track) {
            this.track = this.slider.querySelector('.pdp-slider-track, .slider-track, div');
            if (!this.track) {
                this.track = document.createElement('div');
                this.track.className = 'pdp-slider-track';
                this.slider.appendChild(this.track);
            }
        }
        
        // مسح الـ track وإنشاء شرائح جديدة
        const existingSlides = this.track.querySelectorAll('.pdp-slider-slide, .pdp-slide');
        
        if (existingSlides.length === 0) {
            // إنشاء شريحة لكل صورة
            images.forEach(function(img, index) {
                // تجاهل الصور الصغيرة جداً (أيقونات)
                if (img.classList.contains('pdp-featured-badge') || 
                    img.closest('.pdp-featured-badge') ||
                    img.width < 50) {
                    return;
                }
                
                const slide = document.createElement('div');
                slide.className = 'pdp-slider-slide';
                slide.dataset.index = index;
                
                // نقل الصورة أو استنساخها
                const imgClone = img.cloneNode(true);
                slide.appendChild(imgClone);
                
                self.track.appendChild(slide);
            });
            
            // تحديث قائمة الشرائح
            this.slides = this.track.querySelectorAll('.pdp-slider-slide');
        } else {
            this.slides = existingSlides;
        }
        
        this.totalSlides = this.slides.length;
        
        // إنشاء النقاط إذا لم توجد وهناك أكثر من شريحة
        if (!this.dotsContainer && this.totalSlides > 1) {
            this.createDots();
        }
        
        // إنشاء العداد إذا لم يوجد
        if (!this.counterEl && this.totalSlides > 1) {
            this.createCounter();
        }
    };

    /**
     * إنشاء النقاط
     */
    PDPSlider.prototype.createDots = function() {
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'pdp-slider-dots';
        
        for (let i = 0; i < this.totalSlides; i++) {
            const dot = document.createElement('button');
            dot.className = 'pdp-slider-dot' + (i === 0 ? ' active' : '');
            dot.dataset.index = i;
            dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
            dotsContainer.appendChild(dot);
        }
        
        this.slider.appendChild(dotsContainer);
        this.dotsContainer = dotsContainer;
    };

    /**
     * إنشاء العداد
     */
    PDPSlider.prototype.createCounter = function() {
        const counter = document.createElement('div');
        counter.className = 'pdp-slide-counter';
        counter.innerHTML = '<span class="pdp-slide-current">1</span> / ' + this.totalSlides;
        
        this.slider.appendChild(counter);
        this.counterEl = counter.querySelector('.pdp-slide-current');
    };

    /**
     * التهيئة الرئيسية
     */
    PDPSlider.prototype.init = function() {
        this.setupStyles();
        this.setupSlides();
        this.bindEvents();
        this.bindDots();
        this.goToSlide(0, false);
        
        if (CONFIG.autoplay && this.totalSlides > 1) {
            this.startAutoplay();
        }
    };

    /**
     * إعداد الأنماط الأساسية
     */
    PDPSlider.prototype.setupStyles = function() {
        // إعداد السلايدر
        this.slider.style.position = 'relative';
        this.slider.style.overflow = 'hidden';
        
        // إعداد الـ track
        if (this.track) {
            this.track.style.display = 'flex';
            this.track.style.alignItems = 'center';
            this.track.style.transition = 'transform ' + CONFIG.transitionSpeed + 'ms cubic-bezier(0.4, 0, 0.2, 1)';
            this.track.style.willChange = 'transform';
        }
    };

    /**
     * إعداد الشرائح - بدون قص الصور
     */
    PDPSlider.prototype.setupSlides = function() {
        const self = this;
        
        Array.from(this.slides).forEach(function(slide, index) {
            // إعداد الشريحة
            slide.style.flex = '0 0 100%';
            slide.style.width = '100%';
            slide.style.minWidth = '100%';
            slide.style.display = 'flex';
            slide.style.alignItems = 'center';
            slide.style.justifyContent = 'center';
            slide.style.padding = '10px';
            slide.style.boxSizing = 'border-box';
            
            // إعداد الصورة - بحجمها الطبيعي
            const img = slide.querySelector('img');
            if (img) {
                // تحميل فوري للصورة الأولى
                if (index === 0) {
                    img.removeAttribute('loading');
                    img.setAttribute('fetchpriority', 'high');
                }
                
                // تحميل data-src إذا وجد
                if (img.dataset.src && !img.src) {
                    img.src = img.dataset.src;
                }
                
                // أنماط الصورة - عرض طبيعي بدون قص
                img.style.display = 'block';
                img.style.width = 'auto';
                img.style.height = 'auto';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '280px';
                img.style.objectFit = 'contain';
                img.style.margin = '0 auto';
                img.style.borderRadius = '8px';
                
                // معالجة الأخطاء
                img.onerror = function() {
                    if (CONFIG.debug) {
                        console.warn('⚠️ خطأ في تحميل الصورة:', this.src);
                    }
                    // استبدال بصورة placeholder
                    this.src = 'https://placehold.co/400x300/1D2D51/C9A961?text=Image+Not+Found';
                };
            }
        });
    };

    /**
     * ربط الأحداث
     */
    PDPSlider.prototype.bindEvents = function() {
        const self = this;

        // زر السابق
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.prevSlide();
            });
        }

        // زر التالي
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.nextSlide();
            });
        }

        // إيقاف مؤقت عند التحويم
        if (CONFIG.pauseOnHover) {
            this.slider.addEventListener('mouseenter', function() {
                self.isHovered = true;
                self.pauseProgress();
            });

            this.slider.addEventListener('mouseleave', function() {
                self.isHovered = false;
                self.resumeProgress();
            });
        }

        // دعم اللمس
        if (CONFIG.enableTouch) {
            this.slider.addEventListener('touchstart', function(e) {
                self.touchStartX = e.changedTouches[0].screenX;
                self.isHovered = true;
                self.pauseProgress();
            }, { passive: true });

            this.slider.addEventListener('touchend', function(e) {
                self.touchEndX = e.changedTouches[0].screenX;
                self.handleSwipe();
                self.isHovered = false;
                self.resumeProgress();
            }, { passive: true });
        }

        // دعم لوحة المفاتيح
        if (CONFIG.enableKeyboard) {
            this.slider.setAttribute('tabindex', '0');
            this.slider.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    self.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    self.nextSlide();
                }
            });
        }

        // مراقب الرؤية - إيقاف السلايدر غير المرئي
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        if (!self.isPaused) {
                            self.startAutoplay();
                        }
                    } else {
                        self.stopAutoplay();
                    }
                });
            }, { threshold: 0.2 });
            
            observer.observe(this.slider);
        }
    };

    /**
     * ربط أحداث النقاط
     */
    PDPSlider.prototype.bindDots = function() {
        if (!this.dotsContainer) return;
        
        const self = this;
        const dots = findAllElements(this.dotsContainer, SELECTORS.dot);
        
        Array.from(dots).forEach(function(dot, index) {
            dot.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const slideIndex = parseInt(dot.dataset.index || dot.dataset.slide || index);
                self.goToSlide(slideIndex);
            });
        });
    };

    /**
     * الانتقال إلى شريحة معينة
     */
    PDPSlider.prototype.goToSlide = function(index, animate) {
        if (typeof animate === 'undefined') animate = true;
        
        // حساب الفهرس
        if (index < 0) {
            index = this.totalSlides - 1;
        } else if (index >= this.totalSlides) {
            index = 0;
        }
        
        this.currentIndex = index;

        // تحريك الـ track
        if (this.track) {
            if (!animate) {
                this.track.style.transition = 'none';
            }
            
            this.track.style.transform = 'translateX(-' + (this.currentIndex * 100) + '%)';
            
            if (!animate) {
                const self = this;
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        self.track.style.transition = 'transform ' + CONFIG.transitionSpeed + 'ms cubic-bezier(0.4, 0, 0.2, 1)';
                    });
                });
            }
        }

        // تحديث النقاط
        this.updateDots();

        // تحديث العداد
        this.updateCounter();

        // إعادة تعيين التقدم
        this.resetProgress();
    };

    /**
     * الشريحة التالية
     */
    PDPSlider.prototype.nextSlide = function() {
        this.goToSlide(this.currentIndex + 1);
    };

    /**
     * الشريحة السابقة
     */
    PDPSlider.prototype.prevSlide = function() {
        this.goToSlide(this.currentIndex - 1);
    };

    /**
     * تحديث النقاط
     */
    PDPSlider.prototype.updateDots = function() {
        if (!this.dotsContainer) return;
        
        const dots = findAllElements(this.dotsContainer, SELECTORS.dot);
        const self = this;
        
        Array.from(dots).forEach(function(dot, i) {
            dot.classList.toggle('active', i === self.currentIndex);
        });
    };

    /**
     * تحديث العداد
     */
    PDPSlider.prototype.updateCounter = function() {
        if (this.counterEl) {
            this.counterEl.textContent = this.currentIndex + 1;
        }
    };

    /**
     * معالجة السحب
     */
    PDPSlider.prototype.handleSwipe = function() {
        const diff = this.touchStartX - this.touchEndX;
        const threshold = 50;

        if (Math.abs(diff) > threshold) {
            if (diff > 0) {
                this.nextSlide();
            } else {
                this.prevSlide();
            }
        }
    };

    /**
     * التشغيل التلقائي مع شريط التقدم
     */
    PDPSlider.prototype.startAutoplay = function() {
        if (!CONFIG.autoplay || this.totalSlides <= 1) return;
        
        this.stopAutoplay();
        this.isPaused = false;
        this.progressValue = 0;
        
        const self = this;
        const step = 50;
        const totalSteps = CONFIG.interval / step;
        const increment = 100 / totalSteps;
        
        this.progressTimer = setInterval(function() {
            if (self.isHovered || self.isPaused) return;
            
            self.progressValue += increment;
            
            if (self.progressBar) {
                self.progressBar.style.width = self.progressValue + '%';
            }
            
            if (self.progressValue >= 100) {
                self.nextSlide();
            }
        }, step);
    };

    /**
     * إيقاف التشغيل التلقائي
     */
    PDPSlider.prototype.stopAutoplay = function() {
        if (this.progressTimer) {
            clearInterval(this.progressTimer);
            this.progressTimer = null;
        }
    };

    /**
     * إيقاف مؤقت
     */
    PDPSlider.prototype.pauseProgress = function() {
        this.isPaused = true;
    };

    /**
     * استئناف
     */
    PDPSlider.prototype.resumeProgress = function() {
        this.isPaused = false;
    };

    /**
     * إعادة تعيين التقدم
     */
    PDPSlider.prototype.resetProgress = function() {
        this.progressValue = 0;
        if (this.progressBar) {
            this.progressBar.style.width = '0%';
        }
    };

    // ═══════════════════════════════════════════════════════════════
    // التصدير والتهيئة
    // ═══════════════════════════════════════════════════════════════
    
    window.PDPSlider = PDPSlider;
    window.pdpInitSliders = initSliders;
    window.pdpReinitSliders = function() {
        document.querySelectorAll('[data-pdp-slider-init]').forEach(function(el) {
            delete el.dataset.pdpSliderInit;
        });
        initSliders();
    };

    // بدء التهيئة
    init();

    // إعادة التهيئة عند AJAX
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('ajaxComplete', function() {
            setTimeout(initSliders, 300);
        });
    }

    // MutationObserver لمراقبة التغييرات في DOM
    if ('MutationObserver' in window) {
        const observer = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if (node.classList && (
                                node.classList.contains('pdp-card-slider') ||
                                node.classList.contains('pdp-card') ||
                                node.querySelector && node.querySelector('.pdp-card-slider')
                            )) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(initSliders, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
