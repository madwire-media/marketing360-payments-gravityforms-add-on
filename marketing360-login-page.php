<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="robots" content="noindex, nofollow">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Connect to Marketing 360®</title>
        <script src="/wp-includes/js/jquery/jquery.js" id="jquery-js"></script>
        <link href="https://fonts.googleapis.com/css?family=Roboto:500&amp;display=swap&amp;text=Sign%20in%20with%20Google" rel="stylesheet">
        <style>
            * {
                box-sizing: border-box;
            }
            html {
                color: rgba(0, 0, 0, .65);
                font-size: 14px;
                font-family: -apple-system, BlinkMacSystemFont, Segoe UI, PingFang SC, Hiragino Sans GB, Microsoft YaHei, Helvetica Neue, Helvetica, Arial, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol;
                font-variant: tabular-nums;
                line-height: 1.5;
                font-feature-settings: "tnum";
            }
            body {
                display:flex;
                align-items: center;
                justify-content:center;
                height: 100vh;
                width: 100%;
                padding: 1.5rem;
                color: rgba(0, 0, 0, .65);
                margin:0;
            }
            a {
                color: #2a91cf;
                text-decoration: none;
            }
            img {
                max-width: 100%;
            }
            #kc-page-title {
                font-size: 22px;
                text-align: left;
                margin: 0 0 1.2rem 0;
            }
            .card-pf {
                width:100%;
                max-width:420px;
                position: relative;
                padding: calc(2.5rem + 24px) calc(1.5rem + 24px);
                margin-bottom: 5rem;
                border-radius: 5px;
                box-shadow: 0 5px 10px rgb(0 0 0 / 10%);
                border: 1px solid #e6e6e6;
                background: #ffffff;
            }
            #kc-content {
                width: 100%;
            }
            .form-group {
                display: flex;
                flex-direction: column;
            }
            .form-control {
                border: 1px solid #E6E6E6;
                box-shadow: none;
                border-radius: 5px;
                width: 100%;
                height: 36px;
                line-height: 36px;
                padding: 20px 11px;
                font-size: 1rem;
                transition: border-color 0.2s ease;
                margin: 0.3rem 0;
                box-sizing: border-box;
                transition: border .3s, box-shadow .3s;
            }
            .login-pf-settings {
                margin-top: 0.5rem;
            }
            .login-pf-settings a {
                margin-left: 10px;
            }
            .kc-form-description,
            .kc-form-submit-agreement,
            .login-pf-settings a {
                color: rgba(0, 0, 0, .45);
            }
            #kc-form-buttons {
                margin-top: 1.5rem;
            }
            .btn-primary,
            .btn-secondary,
            .btn-link {
                display: table;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                background: none;
            }
            .btn-primary,
            .btn-secondary {
                width: 100%;
                font-family: -apple-system, BlinkMacSystemFont, Segoe UI, PingFang SC, Hiragino Sans GB, Microsoft YaHei, Helvetica Neue, Helvetica, Arial, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol;
                border-radius: 5px;
                border: none;
                margin-top: 0;
                padding: 0.75rem;
                color: #fff;
                transition: box-shadow 0.2s ease;
            }
            .btn-primary {
                background: #006DD0;
                -webkit-appearance: none;
            }
            .btn-primary:hover,
            .btn-primary:active {
                background: #07569D;
            }
            .login-pf-settings a:hover,
            .login-pf-settings a:active {
                color: #888;
            }
            .form-control:active,
            .form-control:focus {
                outline: 0;
                box-shadow: 0 0 0 2px rgb(0 109 208 / 20%);
                border-color: #238ade;
            }

            .form-control:hover {
                border-color: #238ade;
            }
            #alert-error:empty {
                display:none;
            }
            .alert {
                width: 100%;
                padding: 10px;
                border: 1px solid #c0c0c0;
                margin: 10px 0;
                border-radius: 3px;
            }
            .alert-error {
                background-color: rgba(204, 0, 0, 0.1);
                border-color: #cc0000;
                color: #333333;
            }
            #accounts-list {
                max-width: 100%;
                max-height:300px;
                overflow:auto;
            }
            .m360-account {
                display: flex;
                border-radius: 5px;
                border: 1px solid #e6e6e6;
                padding: 0px 10px;
                margin-bottom: 10px;
                cursor:pointer;
                transition: 0.25s;
            }
            .m360-account-icon {
                max-width: 100px;
                margin-right:10px;
            }
            .display-name {
                margin-bottom:0;
            }
            .account-number {
                margin-top: 0;
                font-weight:400;
                font-size:1em;
            }
            .m360-account:hover,
            .m360-account:focus {
                border-color: #238ade;
            }
        </style>
    </head>
    <body>
        <div class="card-pf">
            <div style="position:absolute;top:-35px;right:-35px;display:flex;z-index:-1;">
                <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dot-matrix" x="0" y="0" width="15" height="15" patternUnits="userSpaceOnUse"><circle cx="13" cy="2" r="2" fill="#e5e6e7"></circle></pattern></defs><rect fill="url(#dot-matrix)" width="100%" height="100%"></rect></svg>
            </div>
            <div style="position:absolute;bottom:-45px;left:-45px;display:flex;z-index:-1;">
                <svg width="150" height="150" version="1.1" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dot-matrix" x="0" y="0" width="15" height="15" patternUnits="userSpaceOnUse"><circle cx="13" cy="2" r="2" fill="#e5e6e7"></circle></pattern></defs><rect fill="url(#dot-matrix)" width="100%" height="100%"></rect></svg>
            </div>
            <header class="login-pf-header">
                <h1 id="kc-page-title">Connect to Marketing 360®</h1>
                <p id="accounts-list-subheading" style="display:none">Click the account you'd like to connect.</p>
            </header>
            <div id="kc-content">
                <div id="kc-content-wrapper">
                    <div id="alert-error" class="alert alert-error"></div>
                    <div id="kc-form">
                        <div id="kc-form-wrapper">
                            <form id="kc-form-login" action="/wp-json/gf_marketing_360_payments/v1/sign_in" method="post">
                                <div class="form-group">
                                        <input tabindex="1" id="username" class="form-control" name="username" value="" type="text" autofocus="" autocomplete="off" placeholder="Email">
                                </div>

                                <div class="form-group">
                                    <input tabindex="2" id="password" class="form-control" name="password" type="password" autocomplete="off" placeholder="Password">
                                </div>

                                <!--<div class="form-group login-pf-settings">
                                    <div id="kc-form-options"></div>
                                        <div class="">
                                                <span><a target="_blank" href="https://login.marketing360.com/auth/realms/marketing360/login-actions/reset-credentials?client_id=mm360v3-front">I forgot my password</a></span>
                                        </div>
                                </div>-->

                                <div id="kc-form-buttons" class="form-group">
                                    <input tabindex="4" class="btn btn-primary btn-block btn-lg" name="login" id="kc-login" type="submit" value="Connect">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="accounts-list" style="display:none"></div>
            </div>
        </div>
        <script>
            (function($) {
                $(document).ready(function() {
                    $('#kc-form-login').submit(onFormSubmit);
                });

                function onFormSubmit(e) {
                    e.preventDefault();
                    
                    const form = e.target;

                    const accountsList = $('#accounts-list');
                    const accountsListSubHeading = $('#accounts-list-subheading');

                    $('#kc-login').val("Connecting...");

                    $.ajax({
                        url: form.action,
                        method: form.method,
                        data: $(this).serialize()
                    })
                    .done(function(response) {
                        if (Array.isArray(response)) {
                            $('#kc-content-wrapper').hide();
                            accountsList.show();
                            accountsListSubHeading.show();
                            response.forEach(function(account) {
                                const html = $(account.html);

                                delete account.html;
                                html.click(function() {
                                    window.opener.postMessage(account);
                                    window.close();
                                })
                                accountsList.append(html);
                            });
                        }
                    })
                    .error(function(response) {
                        $('#alert-error').text(response.responseText);

                        $('#kc-login').val("Connect");
                        console.error(response);
                    })
                }
            })(jQuery)
        </script>
    </body>
</html>