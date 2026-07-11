<?php
include('lang.php');
$mobile_lang = mobile_get_lang();
$mobile_ccode = $_GET['ccode'] ?? '';
$mobile_domain = $_GET['domain'] ?? '';
$mobile_token = $_GET['token'] ?? '';
$safe_lang = htmlspecialchars($mobile_lang, ENT_QUOTES, 'UTF-8');
$safe_ccode = htmlspecialchars((string) $mobile_ccode, ENT_QUOTES, 'UTF-8');
$safe_domain = htmlspecialchars((string) $mobile_domain, ENT_QUOTES, 'UTF-8');
$safe_token = htmlspecialchars((string) $mobile_token, ENT_QUOTES, 'UTF-8');
?>
<html lang="<?php echo $safe_lang; ?>" dir="<?php echo mobile_dir($mobile_lang); ?>">
<head>
    <title><?php echo htmlspecialchars(mobile_t('page_title.history', $mobile_lang)); ?></title>
<?php include('header.php'); ?>
</head>
<body class="history-page" data-ccode="<?php echo $safe_ccode; ?>" data-domain="<?php echo $safe_domain; ?>" data-token="<?php echo $safe_token; ?>" data-lang="<?php echo $safe_lang; ?>">

<div class="history-shell">
    <div class="history-toolbar">
        <div class="history-toolbar-top">
            <div>
                <div class="history-title"><?php echo htmlspecialchars(mobile_t('history.archive_title', $mobile_lang)); ?></div>
                <div class="history-subtitle" id="history_summary"><?php echo htmlspecialchars(mobile_t('history.loading', $mobile_lang)); ?></div>
            </div>
        </div>
        <div class="history-months" id="history_months" role="tablist" aria-label="<?php echo htmlspecialchars(mobile_t('history.filter_month', $mobile_lang)); ?>"></div>
    </div>

    <div id="history_list" class="history-list" aria-live="polite"></div>

    <div class="history-footer">
        <div id="history_empty" class="history-empty" hidden></div>
        <div id="history_loading" class="history-loading" hidden>
            <div class="history-spinner"></div>
            <span><?php echo htmlspecialchars(mobile_t('history.loading', $mobile_lang)); ?></span>
        </div>
        <button type="button" id="history_load_more" class="history-load-more" hidden>
            <?php echo htmlspecialchars(mobile_t('history.load_more', $mobile_lang)); ?>
        </button>
        <div id="history_end" class="history-end" hidden>
            <?php echo htmlspecialchars(mobile_t('history.end', $mobile_lang)); ?>
        </div>
        <div id="history_sentinel" class="history-sentinel" aria-hidden="true"></div>
    </div>
</div>

<script>
(function () {
    var body = document.body;
    var cfg = {
        ccode: body.getAttribute('data-ccode') || '',
        domain: body.getAttribute('data-domain') || '',
        token: body.getAttribute('data-token') || '',
        lang: body.getAttribute('data-lang') || 'en'
    };

    var i18n = {
        all: <?php echo json_encode(mobile_t('history.all', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        loading: <?php echo json_encode(mobile_t('history.loading', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        summary: <?php echo json_encode(mobile_t('history.summary', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        loadMore: <?php echo json_encode(mobile_t('history.load_more', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        end: <?php echo json_encode(mobile_t('history.end', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        empty: <?php echo json_encode(mobile_t('empty_orders', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>,
        error: <?php echo json_encode(mobile_t('history.error', $mobile_lang), JSON_UNESCAPED_UNICODE); ?>
    };

    var state = {
        page: 1,
        month: '',
        loading: false,
        hasMore: true,
        total: 0,
        loaded: 0
    };

    var listEl = document.getElementById('history_list');
    var monthsEl = document.getElementById('history_months');
    var summaryEl = document.getElementById('history_summary');
    var loadingEl = document.getElementById('history_loading');
    var emptyEl = document.getElementById('history_empty');
    var loadMoreBtn = document.getElementById('history_load_more');
    var endEl = document.getElementById('history_end');
    var sentinelEl = document.getElementById('history_sentinel');

    function setLoading(isLoading) {
        state.loading = isLoading;
        loadingEl.hidden = !isLoading;
        loadMoreBtn.disabled = isLoading;
    }

    function updateSummary() {
        if (!state.total) {
            summaryEl.textContent = i18n.empty;
            return;
        }
        summaryEl.textContent = i18n.summary
            .replace('{loaded}', String(state.loaded))
            .replace('{total}', String(state.total));
    }

    function renderMonths(months) {
        var html = '<button type="button" class="history-month-chip' + (state.month === '' ? ' is-active' : '') + '" data-month="">'
            + i18n.all + '</button>';

        (months || []).forEach(function (item) {
            var active = state.month === item.key ? ' is-active' : '';
            html += '<button type="button" class="history-month-chip' + active + '" data-month="' + item.key + '">'
                + item.label + ' <span class="history-month-count">' + item.count + '</span></button>';
        });

        monthsEl.innerHTML = html;
    }

    function updateFooter() {
        emptyEl.hidden = !(state.total === 0 && !state.loading);
        loadMoreBtn.hidden = !(state.hasMore && state.total > 0);
        endEl.hidden = !(!state.hasMore && state.total > 0);
    }

    function loadHistory(reset) {
        if (state.loading) {
            return;
        }
        if (!reset && !state.hasMore) {
            return;
        }

        if (reset) {
            state.page = 1;
            state.hasMore = true;
            state.loaded = 0;
            listEl.innerHTML = '';
            emptyEl.hidden = true;
            endEl.hidden = true;
            loadMoreBtn.hidden = true;
        }

        setLoading(true);

        $.ajax({
            url: 'getOrdershistory.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ccode: cfg.ccode,
                domain: cfg.domain,
                token: cfg.token,
                lang: cfg.lang,
                page: state.page,
                limit: 20,
                month: state.month
            },
            success: function (data) {
                if (!data || !data.ok) {
                    summaryEl.textContent = i18n.error;
                    emptyEl.hidden = false;
                    emptyEl.textContent = i18n.error;
                    return;
                }

                if (reset || state.page === 1) {
                    renderMonths(data.months || []);
                }

                state.total = data.total || 0;
                state.hasMore = !!data.has_more;
                state.loaded = data.loaded || state.loaded;

                if (data.html) {
                    listEl.insertAdjacentHTML('beforeend', data.html);
                }

                if (data.empty) {
                    emptyEl.hidden = false;
                    emptyEl.textContent = data.empty_message || i18n.empty;
                }

                updateSummary();
                updateFooter();

                if (state.hasMore) {
                    state.page += 1;
                }
            },
            error: function () {
                summaryEl.textContent = i18n.error;
                emptyEl.hidden = false;
                emptyEl.textContent = i18n.error;
            },
            complete: function () {
                setLoading(false);
            }
        });
    }

    monthsEl.addEventListener('click', function (event) {
        var btn = event.target.closest('.history-month-chip');
        if (!btn) {
            return;
        }
        state.month = btn.getAttribute('data-month') || '';
        loadHistory(true);
    });

    loadMoreBtn.addEventListener('click', function () {
        loadHistory(false);
    });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && state.hasMore && !state.loading && state.total > 0) {
                    loadHistory(false);
                }
            });
        }, { rootMargin: '120px 0px' });
        observer.observe(sentinelEl);
    }

    loadHistory(true);
})();
</script>

</body>
</html>
