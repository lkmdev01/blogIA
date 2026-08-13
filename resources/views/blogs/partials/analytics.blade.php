@php
    $ga4MeasurementId = $project->ga4_measurement_id;
    $posthogApiKey = $project->posthog_api_key;
    $posthogHost = $project->posthog_host ?: 'https://us.i.posthog.com';
@endphp

@if (filled($ga4MeasurementId) || filled($posthogApiKey))
    <script>
        window.blogIAAnalytics = {
            ga4MeasurementId: @js($ga4MeasurementId),
            posthogApiKey: @js($posthogApiKey),
            posthogHost: @js($posthogHost),
            projectSlug: @js($project->slug),
            projectName: @js($project->name),
            pageType: @js($pageType ?? 'blog'),
            articleSlug: @js($article->slug ?? null),
            articleTitle: @js($article->title ?? null),
            categorySlug: @js($category->slug ?? $selectedCategory?->slug ?? null),
            categoryName: @js($category->name ?? $selectedCategory?->name ?? null),
        };
    </script>

    @if (filled($ga4MeasurementId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () {
                dataLayer.push(arguments);
            };

            gtag('js', new Date());
            gtag('config', '{{ $ga4MeasurementId }}');
        </script>
    @endif

    @if (filled($posthogApiKey))
        <script>
            !function (t, e) {
                let o, n, p, r;

                if (!e.__SV) {
                    window.posthog = e;
                    e._i = [];
                    e.init = function (i, s, a) {
                        function g(t, e) {
                            const o = e.split('.');

                            if (o.length === 2) {
                                t = t[o[0]];
                                e = o[1];
                            }

                            t[e] = function () {
                                t.push([e].concat(Array.prototype.slice.call(arguments, 0)));
                            };
                        }

                        p = t.createElement('script');
                        p.type = 'text/javascript';
                        p.async = true;
                        p.src = (s.api_host || '{{ $posthogHost }}').replace(/\/$/, '') + '/static/array.js';
                        r = t.getElementsByTagName('script')[0];
                        r.parentNode.insertBefore(p, r);

                        let u = e;

                        if (a !== undefined) {
                            u = e[a] = [];
                        } else {
                            a = 'posthog';
                        }

                        u.people = u.people || [];
                        u.toString = function (t) {
                            let e = 'posthog';

                            if (a !== 'posthog') {
                                e += '.' + a;
                            }

                            if (!t) {
                                e += ' (stub)';
                            }

                            return e;
                        };
                        u.people.toString = function () {
                            return u.toString(1) + '.people (stub)';
                        };

                        o = 'init capture register register_once unregister unregister_once alias identify set_config reset opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing startSessionRecording stopSessionRecording sessionRecordingStarted captureException'.split(' ');

                        for (n = 0; n < o.length; n++) {
                            g(u, o[n]);
                        }

                        e._i.push([i, s, a]);
                    };

                    e.__SV = 1;
                }
            }(document, window.posthog || []);

            posthog.init('{{ $posthogApiKey }}', {
                api_host: '{{ $posthogHost }}',
                capture_pageview: false,
                person_profiles: 'identified_only',
            });
        </script>
    @endif
@endif
