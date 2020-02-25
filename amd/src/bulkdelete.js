define(['jquery', 'core/modal_factory', 'core/modal_events'], function($, ModalFactory, ModalEvents) {

    return {
        init: function() {

            function confirm_modal (obj){
                var trigger = $('#create-modal');
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: obj.title,
                    body: obj.body,
                }, trigger).done(function(modal) {
                    modal.setSaveButtonText(obj.save_button);

                    // Figure out what is returned on cancel and continue buttons.
                    // How to change text on buttons
                    modal.getRoot().on(ModalEvents.save, function() {
                        obj.next();
                    });
                    modal.show();
                });
            }

            function get_checks() {
                var els = document.forms["form"].elements;
                var ret = new Array();
                for (var i = 0; i < els.length; i++) {
                    var el = els[i];
                    if (el.type == "checkbox" && el.name.match(/^delete\b/)) {
                        ret.push(el);
                    }
                }
                return ret;
            }

            function check_all(check) {
                var checks = get_checks();
                for (var i = 0; i < checks.length; i++) {
                    checks[i].checked = check.checked;
                }
                document.forms["form"].elements["delete_checked"].disabled = !check.checked;
            }

            function check() {
                var delete_checked = document.forms["form"].elements["delete_checked"];
                var checks = get_checks();
                for (var i = 0; i < checks.length; i++) {
                    if (checks[i].checked) {
                        delete_checked.disabled = false;
                        return;
                    }
                }
                delete_checked.disabled = true;
            }

            $('.bulk-delete-item [id^=delete]').on('click', function(){
                check();
            });
            $('.bulk-delete-select-all input').on('click', function() {
                check_all(this);
            });

            $('.form_submit').on('click', function(){
                var checked_for_delete = $(this);
                console.log(checked_for_delete);

                confirm_modal({
                    'title': 'Slet de valgte aktiviteter?',
                    'body': 'selected activities.',
                    'save_button': 'Save',
                    'next': function() {
                        $('#form').submit();
                    }
                });
            });
        }
    };
});