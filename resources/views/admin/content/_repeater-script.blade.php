@push('scripts')
    <script>
        // Listes modifiables : ajouter, supprimer, monter / descendre une ligne.
        document.querySelectorAll('[data-repeater]').forEach(function (root) {
            var list = root.querySelector('[data-repeater-list]');
            var template = root.querySelector('[data-repeater-template]');
            var add = root.querySelector('[data-repeater-add]');
            var empty = root.querySelector('[data-repeater-empty]');
            var counter = 1000;

            function refresh() {
                var rows = list.querySelectorAll('[data-repeater-row]');
                rows.forEach(function (row, index) {
                    var number = row.querySelector('[data-repeater-number]');
                    if (number) number.textContent = index + 1;
                });
                if (empty) empty.hidden = rows.length > 0;
                add.disabled = rows.length >= Number(add.dataset.max);
            }

            add.addEventListener('click', function () {
                var html = template.innerHTML.replace(/__INDEX__/g, String(counter++));
                list.insertAdjacentHTML('beforeend', html);
                refresh();
                var field = list.lastElementChild.querySelector('input:not([type=hidden]), textarea');
                if (field) field.focus();
            });

            list.addEventListener('click', function (event) {
                var button = event.target.closest('button');
                if (!button) return;
                var row = button.closest('[data-repeater-row]');
                if (button.hasAttribute('data-repeater-remove')) {
                    row.remove();
                } else if (button.hasAttribute('data-repeater-up') && row.previousElementSibling) {
                    list.insertBefore(row, row.previousElementSibling);
                    button.focus();
                } else if (button.hasAttribute('data-repeater-down') && row.nextElementSibling) {
                    list.insertBefore(row.nextElementSibling, row);
                    button.focus();
                }
                refresh();
            });

            refresh();
        });
    </script>
@endpush
