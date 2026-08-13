const initializeBlogAnalytics = () => {
    const config = window.blogIAAnalytics;

    if (!config) {
        return;
    }

    const sharedPayload = {
        project_slug: config.projectSlug,
        project_name: config.projectName,
        page_type: config.pageType,
        article_slug: config.articleSlug,
        article_title: config.articleTitle,
        category_slug: config.categorySlug,
        category_name: config.categoryName,
        page_path: window.location.pathname,
    };

    const sendEvent = (eventName, payload = {}) => {
        const finalPayload = Object.fromEntries(
            Object.entries({
                ...sharedPayload,
                ...payload,
            }).filter(([, value]) => value !== null && value !== undefined && value !== '')
        );

        if (typeof window.gtag === 'function' && config.ga4MeasurementId) {
            window.gtag('event', eventName, finalPayload);
        }

        if (window.posthog && typeof window.posthog.capture === 'function' && config.posthogApiKey) {
            window.posthog.capture(eventName, finalPayload);
        }
    };

    sendEvent('blog_page_view');

    document.querySelectorAll('[data-analytics-event]').forEach((element) => {
        element.addEventListener('click', () => {
            sendEvent(element.dataset.analyticsEvent, {
                cta_location: element.dataset.analyticsLocation,
                cta_label: element.dataset.analyticsLabel,
            });
        });
    });

    const searchForm = document.querySelector('[data-blog-search-form]');

    if (searchForm) {
        searchForm.addEventListener('submit', () => {
            const searchInput = searchForm.querySelector('input[name="search"]');
            const categoryInput = searchForm.querySelector('select[name="category"]');

            sendEvent('blog_search', {
                search_term: searchInput?.value?.trim()?.slice(0, 120) || null,
                selected_category: categoryInput?.value || null,
            });
        });
    }

    if (config.pageType === 'article') {
        const firedDepths = new Set();
        const thresholds = [25, 50, 75, 100];

        const trackScrollDepth = () => {
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
            const percentage = scrollHeight <= 0 ? 100 : Math.min(100, Math.round((scrollTop / scrollHeight) * 100));

            thresholds.forEach((threshold) => {
                if (percentage >= threshold && !firedDepths.has(threshold)) {
                    firedDepths.add(threshold);

                    sendEvent('blog_scroll_depth', {
                        scroll_depth: threshold,
                    });
                }
            });
        };

        window.addEventListener('scroll', trackScrollDepth, { passive: true });
        trackScrollDepth();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeBlogAnalytics);
} else {
    initializeBlogAnalytics();
}
