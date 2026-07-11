(function () {
    const DEFAULT_LANG = 'ar';
    const STORAGE_KEY = 'trakmile_lang';

    let currentLang = getLangFromUrl() || localStorage.getItem(STORAGE_KEY);
    if (currentLang !== 'ar' && currentLang !== 'en') {
        currentLang = DEFAULT_LANG;
    }

    function getTranslations() {
        return window.translations || null;
    }

    function t(key) {
        const dict = getTranslations();
        if (!dict) return key;
        return dict[currentLang]?.[key] ?? dict[DEFAULT_LANG]?.[key] ?? key;
    }

    function refreshIcons() {
        if (typeof lucide === 'undefined' || typeof lucide.createIcons !== 'function') return;
        try {
            lucide.createIcons();
        } catch (err) {
            console.warn('Lucide icons skipped:', err);
        }
    }

    function setMenuToggleIcon(isOpen) {
        const menuToggle = document.getElementById('menuToggle');
        if (!menuToggle) return;
        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menuToggle.innerHTML = isOpen
            ? '<span class="menu-icon" aria-hidden="true">✕</span>'
            : '<span class="menu-icon" aria-hidden="true">☰</span>';
    }

    function setMetaContent(selector, content) {
        const el = document.querySelector(selector);
        if (el && content) el.setAttribute('content', content);
    }

    function updateSeoMeta(lang) {
        document.title = t('meta.title');
        setMetaContent('meta[name="description"]', t('meta.description'));
        setMetaContent('meta[name="keywords"]', t('meta.keywords'));
        setMetaContent('meta[property="og:title"]', t('meta.title'));
        setMetaContent('meta[property="og:description"]', t('meta.ogDescription'));
        setMetaContent('meta[property="og:locale"]', lang === 'ar' ? 'ar_SA' : 'en_US');
        setMetaContent('meta[name="twitter:title"]', t('meta.title'));
        setMetaContent('meta[name="twitter:description"]', t('meta.ogDescription'));

        const schemaEl = document.getElementById('schemaOrg');
        if (schemaEl) {
            const features = t('meta.schemaFeatures').split(',').map(s => s.trim()).filter(Boolean);
            schemaEl.textContent = JSON.stringify({
                '@context': 'https://schema.org',
                '@graph': [
                    {
                        '@type': 'Organization',
                        '@id': 'https://trakmile.com/#organization',
                        name: 'Trakmile',
                        url: 'https://trakmile.com/',
                        logo: 'https://trakmile.com/imgs/logo.png',
                        email: 'info@trakmile.com',
                        description: t('meta.schemaOrgDesc'),
                        sameAs: ['https://demo.trakmile.com'],
                    },
                    {
                        '@type': 'WebSite',
                        '@id': 'https://trakmile.com/#website',
                        url: 'https://trakmile.com/',
                        name: 'Trakmile',
                        description: t('meta.schemaWebDesc'),
                        publisher: { '@id': 'https://trakmile.com/#organization' },
                        inLanguage: ['ar', 'en'],
                    },
                    {
                        '@type': 'SoftwareApplication',
                        '@id': 'https://trakmile.com/#software',
                        name: 'Trakmile',
                        applicationCategory: 'BusinessApplication',
                        operatingSystem: 'Web, Android, iOS',
                        url: 'https://trakmile.com/',
                        description: t('meta.schemaAppDesc'),
                        offers: {
                            '@type': 'Offer',
                            price: '0',
                            priceCurrency: 'SAR',
                            description: t('meta.schemaOfferDesc'),
                        },
                        featureList: features,
                        provider: { '@id': 'https://trakmile.com/#organization' },
                    },
                ],
            });
        }
    }

    function getLangFromUrl() {
        const param = new URLSearchParams(window.location.search).get('lang');
        return param === 'en' || param === 'ar' ? param : null;
    }

    function applyLanguage(lang) {
        const dict = getTranslations();
        if (!dict) {
            console.error('Trakmile i18n: translations not loaded');
            return;
        }

        currentLang = lang;
        try {
            localStorage.setItem(STORAGE_KEY, lang);
        } catch (_) {
            /* private mode */
        }

        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        updateSeoMeta(lang);

        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (key) el.textContent = t(key);
        });

        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            const key = el.getAttribute('data-i18n-html');
            if (key) el.innerHTML = t(key);
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (key) el.placeholder = t(key);
        });

        document.querySelectorAll('[data-i18n-alt]').forEach(el => {
            const key = el.getAttribute('data-i18n-alt');
            if (key) el.setAttribute('alt', t(key));
        });

        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (key) el.setAttribute('title', t(key));
        });

        const langBtn = document.getElementById('langToggle');
        if (langBtn) {
            langBtn.textContent = lang === 'ar' ? 'EN' : 'عربي';
            langBtn.title = lang === 'ar' ? 'Switch to English' : 'التبديل للعربية';
        }

        const mobileLangBtn = document.getElementById('mobileLangToggle');
        if (mobileLangBtn) {
            mobileLangBtn.textContent = lang === 'ar' ? 'English' : 'العربية';
        }

        refreshIcons();
    }

    function toggleLanguage() {
        applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
    }

    function initQuoteModal() {
        const modal = document.getElementById('quoteModal');
        const quoteForm = document.getElementById('quoteForm');
        const formMessage = document.getElementById('formMessage');

    function openModal() {
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        setStickyQuoteVisible(false);
        document.getElementById('mobileNav')?.classList.remove('open');
        document.body.classList.remove('menu-open');
        setMenuToggleIcon(false);
        if (formMessage) {
                formMessage.style.display = 'none';
                formMessage.className = 'form-message';
            }
        }

        function closeModal() {
            if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
        if (typeof window.updateStickyQuote === 'function') window.updateStickyQuote();
        }

        document.querySelectorAll('[data-open-quote]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                openModal();
            });
        });

        document.getElementById('closeModal')?.addEventListener('click', closeModal);
        document.getElementById('cancelModal')?.addEventListener('click', closeModal);

        modal?.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        quoteForm?.addEventListener('submit', async e => {
            e.preventDefault();
            const submitBtn = quoteForm.querySelector('[type="submit"]');
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('name', document.getElementById('quoteName').value.trim());
            formData.append('phone', document.getElementById('quotePhone').value.trim());
            formData.append('email', document.getElementById('quoteEmail').value.trim());
            formData.append('description', document.getElementById('quoteDescription').value.trim());
            formData.append('lang', currentLang);

            try {
                const res = await fetch('api/submit_quote.php', {
                    method: 'POST',
                    body: formData,
                });
                const text = await res.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch {
                    console.error('Invalid JSON response:', text);
                    if (formMessage) {
                        formMessage.style.display = 'block';
                        formMessage.className = 'form-message error';
                        formMessage.textContent = text
                            ? t('modal.fatal') + ' ' + text.substring(0, 200)
                            : t('modal.error');
                    }
                    submitBtn.disabled = false;
                    return;
                }

                if (!formMessage) return;

                formMessage.style.display = 'block';
                if (result.success) {
                    formMessage.className = 'form-message success';
                    formMessage.textContent = t('modal.success');
                    quoteForm.reset();
                    setTimeout(closeModal, 2500);
                } else {
                    formMessage.className = 'form-message error';
                    const detail = result.error ? `\n(${result.error})` : '';
                    formMessage.textContent = (result.message === 'server_error'
                        ? t('modal.serverError')
                        : t('modal.validation')) + detail;
                    if (result.error) console.error('Quote submit error:', result.error);
                }
            } catch {
                if (formMessage) {
                    formMessage.style.display = 'block';
                    formMessage.className = 'form-message error';
                    formMessage.textContent = t('modal.error');
                }
            }

            submitBtn.disabled = false;
        });
    }

    function initMobileMenu() {
        const menuToggle = document.getElementById('menuToggle');
        const mobileNav = document.getElementById('mobileNav');
        if (!menuToggle || !mobileNav) return;

        setMenuToggleIcon(false);

        menuToggle.addEventListener('click', () => {
            const isOpen = mobileNav.classList.toggle('open');
            document.body.classList.toggle('menu-open', isOpen);
            setMenuToggleIcon(isOpen);
        });

        mobileNav.querySelectorAll('a, button').forEach(link => {
            link.addEventListener('click', () => {
                mobileNav.classList.remove('open');
                document.body.classList.remove('menu-open');
                setMenuToggleIcon(false);
            });
        });
    }

    function initScrollEffects() {
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            header?.classList.toggle('scrolled', window.scrollY > 20);
        });

        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('visible'), i * 80);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

        const counterObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseFloat(el.dataset.target);
                const suffix = el.dataset.suffix || '';
                const isDecimal = el.dataset.decimal === 'true';
                const duration = 1800;
                const start = performance.now();

                function animate(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = target * eased;
                    el.textContent = isDecimal
                        ? current.toFixed(1) + suffix
                        : Math.floor(current) + suffix;
                    if (progress < 1) requestAnimationFrame(animate);
                }
                requestAnimationFrame(animate);
                counterObserver.unobserve(el);
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('.stat-value[data-target]').forEach(el => counterObserver.observe(el));
    }

    function setStickyQuoteVisible(show) {
        const sticky = document.getElementById('mobileQuoteSticky');
        if (!sticky) return;
        sticky.classList.toggle('is-visible', show);
        document.body.classList.toggle('sticky-quote-visible', show);
        sticky.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    function initStickyQuote() {
        const sticky = document.getElementById('mobileQuoteSticky');
        const heroQuote = document.getElementById('heroQuoteBtn');
        if (!sticky || !heroQuote) return;

        const mobileMq = window.matchMedia('(max-width: 768px)');
        let observer = null;

        const updateFromEntry = (isIntersecting) => {
            if (!mobileMq.matches) {
                setStickyQuoteVisible(false);
                return;
            }
            setStickyQuoteVisible(!isIntersecting);
        };

        const startObserver = () => {
            if (observer) observer.disconnect();
            if (!mobileMq.matches) {
                setStickyQuoteVisible(false);
                return;
            }
            observer = new IntersectionObserver(([entry]) => {
                updateFromEntry(entry.isIntersecting);
            }, { threshold: 0, rootMargin: '0px' });
            observer.observe(heroQuote);
        };

        mobileMq.addEventListener('change', startObserver);
        startObserver();

        window.updateStickyQuote = () => {
            if (!mobileMq.matches) return;
            const rect = heroQuote.getBoundingClientRect();
            const visible = rect.top < window.innerHeight && rect.bottom > 0;
            setStickyQuoteVisible(!visible);
        };
    }

    function initFaq() {
        document.querySelectorAll('.faq-item').forEach(item => {
            item.querySelector('.faq-question')?.addEventListener('click', () => {
                const wasOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!wasOpen) item.classList.add('open');
            });
        });
    }

    function init() {
        initMobileMenu();
        initQuoteModal();
        initScrollEffects();
        initFaq();
        initStickyQuote();

        document.getElementById('langToggle')?.addEventListener('click', toggleLanguage);
        document.getElementById('mobileLangToggle')?.addEventListener('click', toggleLanguage);

        applyLanguage(currentLang);
        refreshIcons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
