<script>
    // ============================================
    // LANGUAGE SELECTOR
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.getElementById('languageTrigger');
        const dropdown = document.getElementById('languageDropdown');
        const select = document.getElementById('languageSelect');
        const selectedFlag = document.getElementById('selectedFlag');
        const languageText = document.getElementById('languageText');
        const options = dropdown.querySelectorAll('.language-selector-option');

        const flagFiles = {
            'es': '{{ asset("public/images/flags/colombia.png") }}',
            'en': '{{ asset("public/images/flags/usa.png") }}',
            'zh': '{{ asset("public/images/flags/china.png") }}'
        };

        const languageNames = {
            'es': '{{ __('common.espanol') }}',
            'en': '{{ __('common.ingles') }}',
            'zh': '{{ __('common.chino') }}'
        };

        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
        }

        document.addEventListener('click', function (e) {
            if (trigger && dropdown && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        options.forEach(option => {
            option.addEventListener('click', function () {
                const value = this.dataset.value;
                if (select) select.value = value;

                const img = selectedFlag.querySelector('img');
                if (img) {
                    img.src = flagFiles[value] || flagFiles['es'];
                }
                if (languageText) {
                    languageText.textContent = languageNames[value] || languageNames['es'];
                }

                dropdown.classList.remove('show');
                changeLanguage(value);
            });
        });
    });

    function changeLanguage(locale) {
        const url = '{{ route("language.switch", ["locale" => "__LOCALE__"]) }}'.replace('__LOCALE__', locale);
        window.location.href = url;
    }
</script>
