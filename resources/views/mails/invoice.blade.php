<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="zxx"> <!--<![endif]-->
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{$settings->site->site_title}}</title>
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="icon" type="image/png" href="favicon.png">
<link rel='stylesheet' type='text/css' href="https://gridetech.com/assets/plugins/font-awesome-4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>


</style>
	</head>
	<body style="background: #f7f7f7;">
        <div style="box-shadow:0 0 10px #dad9d9; display: block;width: 750px;min-height: 500px;justify-content: center;margin: 20px auto;box-sizing: border-box;background: #fff;padding: 40px 30px;font-family: sans-serif;border: 4px;border-color: #fff; border-radius: 10px;">
            <div style="width: 100%; text-align: center; margin-bottom: 30px;">
                <div class="logo"><img style="width: 130px;" src="{{$settings->site->site_logo}}"></div>
            </div>
            <h3> Your Ride Invoice </h3>

            <div class="taxi-service">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tbody>
        <tr>
            <td bgcolor="#ffffff" valign="top" style="padding-top: 28px; padding-bottom: 0; padding-left: 0px; padding-right: 0px;">
                <table cellpadding="0" cellspacing="0" border="0" width="49.5%" align="left">
                    <tbody>
                        <tr>
                            <td style="padding-left: 14px; padding-right: 14px;">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td align="center" style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #333333; font-size: 16px; padding-bottom: 16px; font-weight: bold;">Ride Details</td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <?php
                                                    $map_icon_start = '';
                                                    //asset('asset/img/marker-start.png');
                                                    $map_icon_end = '';
                                                    //asset('asset/img/marker-end.png');

                                                    $static_map = "https://maps.googleapis.com/maps/api/staticmap?".
                                                        "autoscale=1".
                                                        "&size=298x298".
                                                        "&maptype=terrian".
                                                        "&format=png".
                                                        "&visual_refresh=true".
                                                        "&markers=icon:".$map_icon_start."%7C".$data['body']['s_latitude'].",".$data['body']['s_longitude'].
                                                        "&markers=icon:".$map_icon_end."%7C".$data['body']['d_latitude'].",".$data['body']['d_longitude'].
                                                        "&path=color:0x000000|weight:3|enc:".$data['body']['route_key'].
                                                        "&key=".$settings->site->server_key;
                                                ?>
                                                <img
                                                    src= "{{$static_map}}"
                                                    class="CToWUd"
                                                />

                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 9px 0 0 0;">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td valign="top" style="border-bottom: 1px solid #eeeeee; padding-bottom: 10px; padding-left: 14px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left" style="width: 42px; padding-left: 2px;" width="42">
                                                                                <img
                                                                                    height="50"
                                                                                    style="height: 50px;"
                                                                                    src="{{$data['payment']['provider']['picture']}}"
                                                                                    alt=""
                                                                                    class="CToWUd"
                                                                                />
                                                                            </td>
                                                                            <td align="left" style="padding-left: 16px;">
                                                                                <table>
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 16px; line-height: 18px;">{{$data['payment']['provider']['first_name']}} {{$data['payment']['provider']['last_name']}}</td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 12px !important; font-size: 10px !important;">
                                                                <img
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    style="width: 100%;"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 0 0 0 0;">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td valign="top" style="border-bottom: 1px solid #eeeeee; padding-bottom: 7px; padding-left: 14px; padding-top: 5px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="left" style="width: 42px; padding-left: 2px;" width="42">
                                                                                <img
                                                                                    width="38"
                                                                                    style="width: 38px;"
                                                                                    src="https://ci5.googleusercontent.com/proxy/D1UgavF73C_fwK1oPEaOTouPDkXwM2lNXOBRV0hJV10qnu1xQSY-m_e_WLz74C-hODr91Dhedi4kqmVfxK27mZjxMVUGqmiwJA=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_Mini_Icon"
                                                                                    alt=""
                                                                                    class="CToWUd"
                                                                                />
                                                                            </td>
                                                                            <td
                                                                                align="left"
                                                                                style="padding-top: 4px; font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #000000; font-size: 14px; padding-left: 16px; line-height: 16px;"
                                                                            >
                                                                                {{$data['body']['provider_vehicle']['vehicle_model']}}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px;">
                                                                <img
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    style="width: 100%;"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr style="vertical-align: top; text-align: left; display: block; background-color: #ffffff; padding-bottom: 10px; padding-top: 5px;" align="left" bgcolor="#ffffff">
                                            <td style="word-break: break-word; border-collapse: collapse !important; vertical-align: top; text-align: left; display: inline-block; padding: 10px 0 0 14px;" align="left" valign="top">
                                                <table style="border-spacing: 0; border-collapse: collapse; vertical-align: top; text-align: left; width: auto; padding: 0;">
                                                    <tbody>
                                                        <tr style="vertical-align: top; text-align: left; width: 100%; padding-top: 5px;" align="left">
                                                            <td
                                                                style="
                                                                    word-break: break-word;
                                                                    border-collapse: collapse !important;
                                                                    vertical-align: top;
                                                                    text-align: left;
                                                                    display: table-cell;
                                                                    width: 80px !important;
                                                                    line-height: 16px;
                                                                    height: auto;
                                                                    padding: 0 0 0 0;
                                                                "
                                                                align="left"
                                                                valign="top"
                                                            >
                                                                <span style="font-size: 14px; font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; font-weight: normal; color: #000000 !important;">
                                                                    <span> <a style="text-decoration: none !important; color: #000000 !important;" rel="noreferrer">{{$data['body']['started_time']}}</a> </span>
                                                                </span>
                                                            </td>
                                                            <td
                                                                rowspan="2"
                                                                style="
                                                                    word-break: break-word;
                                                                    border-collapse: collapse !important;
                                                                    vertical-align: top;
                                                                    text-align: left;
                                                                    display: table-cell;
                                                                    width: 17px !important;
                                                                    padding: 3px 2px 10px 2px;
                                                                "
                                                                align="left"
                                                                valign="top"
                                                            >
                                                                <img
                                                                    width="6"
                                                                    height="63px"
                                                                    src="https://ci4.googleusercontent.com/proxy/-4JN05r1BHpJig7fZ3_u5exztTTZiUqZw80LlR1doUR3spXgpWjsSPn6iSq3MxLogXle5FcNORTYc0vdmpQnvuduxWgcHQa9=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_src_dest"
                                                                    style="outline: none; text-decoration: none; float: left; clear: both; display: block; width: 6px !important; height: 63px; padding-top: 5px;"
                                                                    align="left"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                            <td
                                                                style="
                                                                    word-break: break-word;
                                                                    border-collapse: collapse !important;
                                                                    vertical-align: top;
                                                                    text-align: left;
                                                                    display: table-cell;
                                                                    width: 197px;
                                                                    line-height: 16px;
                                                                    height: 57px;
                                                                    padding: 0 10px 10px 0;
                                                                "
                                                                align="left"
                                                                valign="top"
                                                            >
                                                                <span style="font-size: 14px; font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #000000 !important; line-height: 16px; text-decoration: none;">
                                                                     {{$data['body']['s_address']}}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr style="vertical-align: top; text-align: left; width: 100%; padding: 0;" align="left">
                                                            <td
                                                                style="
                                                                    word-break: break-word;
                                                                    border-collapse: collapse !important;
                                                                    vertical-align: top;
                                                                    text-align: left;
                                                                    display: table-cell;
                                                                    width: 80px !important;
                                                                    line-height: 16px;
                                                                    height: auto;
                                                                    padding: 0 0 0 0;
                                                                "
                                                                align="left"
                                                                valign="top"
                                                            >
                                                                <span style="font-size: 14px; font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; font-weight: normal; color: #000000 !important;">
                                                                    <span> <a style="text-decoration: none !important; color: #000000 !important;" rel="noreferrer">{{$data['body']['finished_time']}}</a> </span>
                                                                </span>
                                                            </td>
                                                            <td
                                                                style="
                                                                    word-break: break-word;
                                                                    border-collapse: collapse !important;
                                                                    vertical-align: top;
                                                                    text-align: left;
                                                                    display: table-cell;
                                                                    width: 197px;
                                                                    line-height: 16px;
                                                                    height: auto;
                                                                    padding: 0 0px 0 0;
                                                                "
                                                                align="left"
                                                                valign="top"
                                                            >
                                                                <span> {{$data['body']['d_address']}}</span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table cellpadding="0" cellspacing="0" border="0" width="49.5%" align="left">
                    <tbody>
                        <tr>
                            <td style="padding-right: 14px; padding-left: 14px;">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td align="center" style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #333333; font-size: 16px; padding-bottom: 16px; font-weight: bold; border-bottom: 1px solid #d7d7d7;">
                                                Bill Details
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-top: 6px; padding-bottom: 6px; padding-left: 7px; padding-right: 13px; background-color: #f3f3f3;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="72%" style="width: 72%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #000000;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                Your Trip
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <table cellpadding="0" cellspacing="0" border="0" width="27%" style="width: 27%;" align="right">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table align="right">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #000000;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                               
                                                                                                                {{$data['body']['currency']}}{{$data['payment']['total']}}
                                                                                                                
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px !important; padding: 0 0 0 0; font-size: 13px;">
                                                                <img
                                                                    style="width: 100%;"
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-left: 7px; padding-right: 13px; padding-top: 3px; padding-bottom: 3px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="72%" style="width: 72%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #707070;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                Coupon Savings
                                                                                                                <span style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #707070; font-size: 13px;">
                                                                                                                    <br />
                                                                                                                    (50OLA)
                                                                                                                </span>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <table cellpadding="0" cellspacing="0" border="0" width="27%" style="width: 27%;" align="right">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table align="right">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #707070;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                - {{$data['body']['currency']}}{{$data['payment']['discount']}}
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px !important; padding: 0 0 0 0; font-size: 13px;">
                                                                <img
                                                                    style="width: 100%;"
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-left: 7px; padding-right: 13px; padding-bottom: 2px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="72%" style="width: 72%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #707070;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                Toll/Parking Fee
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <table cellpadding="0" cellspacing="0" border="0" width="27%" style="width: 27%;" align="right">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table align="right">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #707070;
                                                                                                                    font-size: 14px;
                                                                                                                    font-weight: normal;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                 {{$data['body']['currency']}}{{$data['payment']['toll_charge']}}
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px !important; padding: 0 0 0 0; font-size: 13px;">
                                                                <img
                                                                    style="width: 100%;"
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-top: 6px; padding-bottom: 6px; padding-left: 7px; padding-right: 13px; background-color: #f3f3f3;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="72%" style="width: 72%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #000000;
                                                                                                                    font-size: 16px;
                                                                                                                    font-weight: bold;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                Total Bill
                                                                                                                <span
                                                                                                                    style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #707070; font-size: 13px; font-weight: normal;"
                                                                                                                >
                                                                                                                    (rounded off)
                                                                                                                </span>
                                                                                                                <span style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #707070; font-size: 13px;">
                                                                                                                    <br />
                                                                                                                    Includes {{$data['body']['currency']}}{{$data['payment']['tax']}} Taxes
                                                                                                                </span>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <table cellpadding="0" cellspacing="0" border="0" width="27%" style="width: 27%;" align="right">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table align="right">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                style="
                                                                                                                    font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif;
                                                                                                                    color: #000000;
                                                                                                                    font-size: 16px;
                                                                                                                    font-weight: bold;
                                                                                                                    line-height: 18px;
                                                                                                                "
                                                                                                            >
                                                                                                                @if($data['payment']['cash'] > 0)
                                                                                                                {{$data['body']['currency']}}{{$data['payment']['cash']}}
                                                                                                                @else
                                                                                                                {{$data['body']['currency']}}{{$data['payment']['card']}}
                                                                                                                @endif
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px !important; padding: 0 0 0 0; font-size: 13px;">
                                                                <img
                                                                    style="width: 100%;"
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <!-- <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-left: 7px; padding-top: 10px; padding-right: 13px; padding-bottom: 0px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #707070; font-size: 13px; line-height: 18px;"
                                                                                                            >
                                                                                                                Click
                                                                                                                <a href="#">
                                                                                                                    here
                                                                                                                </a>
                                                                                                                to get a copy of your invoice. Invoice available till 27/08/2020<br />
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #ffffff; line-height: 8px !important; padding: 0 0 0 0; font-size: 13px;">
                                                                <img
                                                                    src="https://ci5.googleusercontent.com/proxy/lmwK56OC4yjGRVeNTPP_27AftdUtV-9dvZAC5i9QusYZiMsgXSmlD9M63EWix3anmcSB5m4gFvgXiZ5rjjz7iInMK33HurJCrBg=s0-d-e1-ft#http://d2xfkvgwru9u2c.cloudfront.net/Invoice_White_line"
                                                                    style="width: 100%;"
                                                                    class="CToWUd"
                                                                />
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr> -->
                                        <tr>
                                            <td bgcolor="#ffffff">
                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td border="0" cellpadding="0" cellspacing="0" valign="top" style="padding-left: 7px; padding-top: 5px; padding-right: 13px; padding-bottom: 0px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%;" align="left">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="padding: 0 0 0 0;">
                                                                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td align="left">
                                                                                                <table>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td
                                                                                                                align="left"
                                                                                                                style="font-family: Helvetica, 'Helvetica Neue', Arial, sans-serif; color: #707070; font-size: 13px; line-height: 18px;"
                                                                                                            >
                                                                                                                We've fulfilled our promise to take you to destination for pre-agreed Total Fare. Modifying the drop/route can change this fare.
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>

            </div>
        </div>
        </div>
	</body>
</html>
