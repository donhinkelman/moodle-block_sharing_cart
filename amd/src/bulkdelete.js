define(['jquery', 'core/modal_factory', 'core/modal_events'], function ($, ModalFactory, ModalEvents) {

    return {
        init: function () {

            /**
             *  Returns a localized string
             *
             *  @param {String} identifier
             *  @return {String}
             */
            function str(identifier) {
                return M.str.block_sharing_cart[identifier] || M.str.moodle[identifier];
            }

            /**
             *
             * @param object
             */
            function confirm_modal(object) {
                var trigger = $('#create-modal');
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: object.title,
                    body: object.body,
                }, trigger).done(function (modal) {
                    modal.setSaveButtonText(object.save_button);

                    modal.getRoot().on(ModalEvents.save, function () {
                        object.next();
                    });

                    // Remove modal from html.
                    modal.getRoot().on(ModalEvents.hidden, function () {
                        $('.modal.moodle-has-zindex').remove();
                    });
                    modal.show();
                });
            }

            /**
             *
             * @returns {any[]}
             */
            function get_checks() {
                var elements = $('form :checkbox[name^="delete"]');
                return elements;
            }

            /**
             *
             * @param check
             */
            function check_all(check) {
                var checks = get_checks();
                $(checks).prop('checked', check.checked);

                $('form :button[name ="delete_checked"]').prop('disabled', !check.checked);
            }

            /**
             *
             */
            function check() {
                var delete_checked = $('form :button[name^="delete_checked"]');
                var checks = get_checks();
                var checked_checkbox = false;

                $(checks).each(function (i, val) {
                    if ($(val).prop('checked')) {
                        checked_checkbox = true;
                        return false;
                    }
                });

                delete_checked.prop('disabled', !checked_checkbox);
                $('.bulk-delete-select-all :checkbox').prop('checked', checked_checkbox);
            }

            /**
             * Check activity button
             */
            $('.bulk-delete-item [id^=delete]').on('click', function () {
                check();
            });

            /**
             * Select all checkbox.
             */
            $('.bulk-delete-select-all input').on('click', function () {
                check_all(this);
            });

            /**
             * Delete selected, opens modal for confirmation.
             */
            $('.form_submit').on('click', function () {
                var modal_body = '<ul>';
                var selected_input = $('.bulk-delete-item input:checked');
                $(selected_input).each(function () {
                    var label = $('label[for="' + this.id + '"]');
                    modal_body += '<li>' + label.text() + '</li>';
                });
                modal_body += '</ul>';

                confirm_modal({
                    'title': str('modal_bulkdelete_title'),
                    'body': modal_body,
                    'save_button': str('modal_bulkdelete_confirm'),
                    'next': function () {
                        $('#form').submit();
                    }
                });
            });
        }
    };
});
