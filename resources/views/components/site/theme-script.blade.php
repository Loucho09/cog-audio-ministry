{{-- Apply the theme before the page paints (no flash): saved choice first, otherwise the device setting.
     Also adds the "js" class before paint, so the scroll reveal (data-reveal) only hides content when scripts run.
     Used in every layout's <head>. If a Content-Security-Policy is added later, this inline script needs a nonce or hash. --}}
<script>
    (function () {
        document.documentElement.classList.add('js');
        try {
            var saved = localStorage.getItem('theme');
            var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
