<script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('tema');
            var followsSystem = !savedTheme && window.matchMedia &&
                window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', savedTheme === 'gelap' || !!followsSystem);
        } catch (e) {}
    })();
</script>
