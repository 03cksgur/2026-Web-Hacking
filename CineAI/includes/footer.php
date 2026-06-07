    </main>
    <footer>
        <div class="container">
            &copy; <?php echo date('Y'); ?> CineAI. All rights reserved. <br>
            <span style="font-size: 0.8rem; margin-top: 5px; display: inline-block;">Powered by PHP, SQLite & Google Gemini API</span>
        </div>
    </footer>

    <!-- Global Caps Lock Warning -->
    <div id="capslock-warning" style="display:none; position:absolute; background:var(--danger-color, #ef4444); color:white; padding:6px 12px; border-radius:6px; font-size:0.85rem; font-weight:bold; z-index:9999; box-shadow:0 4px 15px rgba(0,0,0,0.3); pointer-events:none;">
      ⚠️ Caps Lock이 켜져 있습니다.
    </div>
    <script>
    function checkCapsLock(e) {
        if (e.target && e.target.type === 'password') {
            const warning = document.getElementById('capslock-warning');
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                warning.style.display = 'block';
                const rect = e.target.getBoundingClientRect();
                warning.style.top = (rect.top + window.scrollY - 35) + 'px';
                warning.style.left = (rect.left + window.scrollX) + 'px';
            } else {
                warning.style.display = 'none';
            }
        }
    }
    document.addEventListener('keydown', checkCapsLock);
    document.addEventListener('keyup', checkCapsLock);
    document.addEventListener('click', checkCapsLock);
    document.addEventListener('focusout', function(e) {
        if (e.target && e.target.type === 'password') {
            document.getElementById('capslock-warning').style.display = 'none';
        }
    });
    </script>
</body>
</html>
