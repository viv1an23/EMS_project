$(document).ready(function () {
    $('.code_info').hide();
    $('#err_change_old_password').hide();
    $('#changePasswordForm').validate({
        'errorElement': 'span',
        'errorClass': 'text-danger',
        rules: {
            'change_old_password': {
                required: true,
            },
            'change_new_password': {
                required: true,
            },
            'change_new_cpassword': {
                required: true,
                equalTo: "#change_new_password"
            },
            'change_code': {
                required: true,
            },
            'change_otp_code' : {
                required: true,
            }
        }
    });
    $('#changePasswordBtn').click(function(event){
        event.preventDefault();
        if($('#changePasswordForm').valid()){
            $.ajax({
                url: '/admin/changepassword',
                type: 'post',
                data: {'formData': $('#changePasswordForm').serialize()},
                success: function (result) {

                    switch (result) {
                        case '0' :
                            $('.code_info').show();
                            $('#message').text('Mail Is Send Please Enter OTP');
                            break;
                        case '1' :
                            $('#change_close').click();
                            $('#logout').click();
                            break;
                    }
                }
            });
        }
    });
})