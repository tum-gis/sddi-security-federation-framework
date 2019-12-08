<!DOCTYPE html>
<html lang="en">

<head>
    <title>Please Approve Application</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>

<body>

<?php include(__DIR__ . '/header.php')?>

<div class="content">
  <div class="container" id="content">
            <div class="row">
                <div class="col-sm-12">
                    <h4>Hello <?php (isset($claims['preferred_username'])) ? print $claims['preferred_username'] : print '' ?></h4>
                    <p>Do you authorize the Application </p>
                </div>
            </div>
            <div class="row application-logo">
                <div class="col">
		    <img class="img-responsive" height="70" src="<?php print $payload['logo_uri']?>"/>
                </div>
	    </div>
	    <div class="row application-name">
                <div class="col">
                    <b><?php print $payload['client_name']?></b>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <p>operated by <?php isset($payload['operator_name']) ? print $payload['operator_name'] : print "(unknown)"?> located in 
        <?php    $countries = array
        (
	'AF' => 'Afghanistan',
	'AX' => 'Aland Islands',
	'AL' => 'Albania',
	'DZ' => 'Algeria',
	'AS' => 'American Samoa',
	'AD' => 'Andorra',
	'AO' => 'Angola',
	'AI' => 'Anguilla',
	'AQ' => 'Antarctica',
	'AG' => 'Antigua And Barbuda',
	'AR' => 'Argentina',
	'AM' => 'Armenia',
	'AW' => 'Aruba',
	'AU' => 'Australia',
	'AT' => 'Austria',
	'AZ' => 'Azerbaijan',
	'BS' => 'Bahamas',
	'BH' => 'Bahrain',
	'BD' => 'Bangladesh',
	'BB' => 'Barbados',
	'BY' => 'Belarus',
	'BE' => 'Belgium',
	'BZ' => 'Belize',
	'BJ' => 'Benin',
	'BM' => 'Bermuda',
	'BT' => 'Bhutan',
	'BO' => 'Bolivia',
	'BA' => 'Bosnia And Herzegovina',
	'BW' => 'Botswana',
	'BV' => 'Bouvet Island',
	'BR' => 'Brazil',
	'IO' => 'British Indian Ocean Territory',
	'BN' => 'Brunei Darussalam',
	'BG' => 'Bulgaria',
	'BF' => 'Burkina Faso',
	'BI' => 'Burundi',
	'KH' => 'Cambodia',
	'CM' => 'Cameroon',
	'CA' => 'Canada',
	'CV' => 'Cape Verde',
	'KY' => 'Cayman Islands',
	'CF' => 'Central African Republic',
	'TD' => 'Chad',
	'CL' => 'Chile',
	'CN' => 'China',
	'CX' => 'Christmas Island',
	'CC' => 'Cocos (Keeling) Islands',
	'CO' => 'Colombia',
	'KM' => 'Comoros',
	'CG' => 'Congo',
	'CD' => 'Congo, Democratic Republic',
	'CK' => 'Cook Islands',
	'CR' => 'Costa Rica',
	'CI' => 'Cote D\'Ivoire',
	'HR' => 'Croatia',
	'CU' => 'Cuba',
	'CY' => 'Cyprus',
	'CZ' => 'Czech Republic',
	'DK' => 'Denmark',
	'DJ' => 'Djibouti',
	'DM' => 'Dominica',
	'DO' => 'Dominican Republic',
	'EC' => 'Ecuador',
	'EG' => 'Egypt',
	'SV' => 'El Salvador',
	'GQ' => 'Equatorial Guinea',
	'ER' => 'Eritrea',
	'EE' => 'Estonia',
	'ET' => 'Ethiopia',
	'FK' => 'Falkland Islands (Malvinas)',
	'FO' => 'Faroe Islands',
	'FJ' => 'Fiji',
	'FI' => 'Finland',
	'FR' => 'France',
	'GF' => 'French Guiana',
	'PF' => 'French Polynesia',
	'TF' => 'French Southern Territories',
	'GA' => 'Gabon',
	'GM' => 'Gambia',
	'GE' => 'Georgia',
	'DE' => 'Germany',
	'GH' => 'Ghana',
	'GI' => 'Gibraltar',
	'GR' => 'Greece',
	'GL' => 'Greenland',
	'GD' => 'Grenada',
	'GP' => 'Guadeloupe',
	'GU' => 'Guam',
	'GT' => 'Guatemala',
	'GG' => 'Guernsey',
	'GN' => 'Guinea',
	'GW' => 'Guinea-Bissau',
	'GY' => 'Guyana',
	'HT' => 'Haiti',
	'HM' => 'Heard Island & Mcdonald Islands',
	'VA' => 'Holy See (Vatican City State)',
	'HN' => 'Honduras',
	'HK' => 'Hong Kong',
	'HU' => 'Hungary',
	'IS' => 'Iceland',
	'IN' => 'India',
	'ID' => 'Indonesia',
	'IR' => 'Iran, Islamic Republic Of',
	'IQ' => 'Iraq',
	'IE' => 'Ireland',
	'IM' => 'Isle Of Man',
	'IL' => 'Israel',
	'IT' => 'Italy',
	'JM' => 'Jamaica',
	'JP' => 'Japan',
	'JE' => 'Jersey',
	'JO' => 'Jordan',
	'KZ' => 'Kazakhstan',
	'KE' => 'Kenya',
	'KI' => 'Kiribati',
	'KR' => 'Korea',
	'KW' => 'Kuwait',
	'KG' => 'Kyrgyzstan',
	'LA' => 'Lao People\'s Democratic Republic',
	'LV' => 'Latvia',
	'LB' => 'Lebanon',
	'LS' => 'Lesotho',
	'LR' => 'Liberia',
	'LY' => 'Libyan Arab Jamahiriya',
	'LI' => 'Liechtenstein',
	'LT' => 'Lithuania',
	'LU' => 'Luxembourg',
	'MO' => 'Macao',
	'MK' => 'Macedonia',
	'MG' => 'Madagascar',
	'MW' => 'Malawi',
	'MY' => 'Malaysia',
	'MV' => 'Maldives',
	'ML' => 'Mali',
	'MT' => 'Malta',
	'MH' => 'Marshall Islands',
	'MQ' => 'Martinique',
	'MR' => 'Mauritania',
	'MU' => 'Mauritius',
	'YT' => 'Mayotte',
	'MX' => 'Mexico',
	'FM' => 'Micronesia, Federated States Of',
	'MD' => 'Moldova',
	'MC' => 'Monaco',
	'MN' => 'Mongolia',
	'ME' => 'Montenegro',
	'MS' => 'Montserrat',
	'MA' => 'Morocco',
	'MZ' => 'Mozambique',
	'MM' => 'Myanmar',
	'NA' => 'Namibia',
	'NR' => 'Nauru',
	'NP' => 'Nepal',
	'NL' => 'Netherlands',
	'AN' => 'Netherlands Antilles',
	'NC' => 'New Caledonia',
	'NZ' => 'New Zealand',
	'NI' => 'Nicaragua',
	'NE' => 'Niger',
	'NG' => 'Nigeria',
	'NU' => 'Niue',
	'NF' => 'Norfolk Island',
	'MP' => 'Northern Mariana Islands',
	'NO' => 'Norway',
	'OM' => 'Oman',
	'PK' => 'Pakistan',
	'PW' => 'Palau',
	'PS' => 'Palestinian Territory, Occupied',
	'PA' => 'Panama',
	'PG' => 'Papua New Guinea',
	'PY' => 'Paraguay',
	'PE' => 'Peru',
	'PH' => 'Philippines',
	'PN' => 'Pitcairn',
	'PL' => 'Poland',
	'PT' => 'Portugal',
	'PR' => 'Puerto Rico',
	'QA' => 'Qatar',
	'RE' => 'Reunion',
	'RO' => 'Romania',
	'RU' => 'Russian Federation',
	'RW' => 'Rwanda',
	'BL' => 'Saint Barthelemy',
	'SH' => 'Saint Helena',
	'KN' => 'Saint Kitts And Nevis',
	'LC' => 'Saint Lucia',
	'MF' => 'Saint Martin',
	'PM' => 'Saint Pierre And Miquelon',
	'VC' => 'Saint Vincent And Grenadines',
	'WS' => 'Samoa',
	'SM' => 'San Marino',
	'ST' => 'Sao Tome And Principe',
	'SA' => 'Saudi Arabia',
	'SN' => 'Senegal',
	'RS' => 'Serbia',
	'SC' => 'Seychelles',
	'SL' => 'Sierra Leone',
	'SG' => 'Singapore',
	'SK' => 'Slovakia',
	'SI' => 'Slovenia',
	'SB' => 'Solomon Islands',
	'SO' => 'Somalia',
	'ZA' => 'South Africa',
	'GS' => 'South Georgia And Sandwich Isl.',
	'ES' => 'Spain',
	'LK' => 'Sri Lanka',
	'SD' => 'Sudan',
	'SR' => 'Suriname',
	'SJ' => 'Svalbard And Jan Mayen',
	'SZ' => 'Swaziland',
	'SE' => 'Sweden',
	'CH' => 'Switzerland',
	'SY' => 'Syrian Arab Republic',
	'TW' => 'Taiwan',
	'TJ' => 'Tajikistan',
	'TZ' => 'Tanzania',
	'TH' => 'Thailand',
	'TL' => 'Timor-Leste',
	'TG' => 'Togo',
	'TK' => 'Tokelau',
	'TO' => 'Tonga',
	'TT' => 'Trinidad And Tobago',
	'TN' => 'Tunisia',
	'TR' => 'Turkey',
	'TM' => 'Turkmenistan',
	'TC' => 'Turks And Caicos Islands',
	'TV' => 'Tuvalu',
	'UG' => 'Uganda',
	'UA' => 'Ukraine',
	'AE' => 'United Arab Emirates',
	'GB' => 'United Kingdom',
	'US' => 'United States',
	'UM' => 'United States Outlying Islands',
	'UY' => 'Uruguay',
	'UZ' => 'Uzbekistan',
	'VU' => 'Vanuatu',
	'VE' => 'Venezuela',
	'VN' => 'Viet Nam',
	'VG' => 'Virgin Islands, British',
	'VI' => 'Virgin Islands, U.S.',
	'WF' => 'Wallis And Futuna',
	'EH' => 'Western Sahara',
	'YE' => 'Yemen',
	'ZM' => 'Zambia',
	'ZW' => 'Zimbabwe',
    );
    		    isset($payload['operator_country']) ? print $countries[$payload['operator_country']] : print "(unknown)"?>
    		    </p>
    		    <p>
  to access the following personal data collected for the current session?
  <?php
    if (empty($claims)) {
        print "<p><b>No information collected from the IdP</b></p>";
    }
    else {
        $non_keys = array('username', 'password', 'first_name', 'last_name');
        $non_values = array('null', '0000-00-00 00:00:00');
        print "<table>";
        foreach ($claims as $key=>$val )
        {
             if ((!in_array($key, $non_keys)) && (!in_array($val, $non_values)))
                 print '<tr><td style="padding:0 15px 0 15px;"><b>'.$key.'</b></td><td style="padding:0 15px 0 15px;">' .$val."</tr>";
        }
        print "</table>";
    }
  ?>
    		    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
		    <?php $eea_countries = array("AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "DE", "GR", "HU", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "SE", "UK", "IS", "LI", "NO"); (in_array($payload['operator_country'], $eea_countries)) ? print '
			<p id="agree_gdpr_privacy" style="display: block;">
                        The operator of this application has its seat inside the EU/EAA. Therefore, the operator of this application is contractually
                        bound to comply with the EU
                        <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.ENG" target="_GDPR">General Data Protection Regulation (GDPR)</a>.
                    </p>' : print '
                    <p id="agree_non_gdpr_privacy" style="display: block;">
                        The operator of this application has its seat outside the EU/EAA and thus in a country where data protection regulations
                        might be less stringent by law. However, the operator of this application is contractually bound
                        to comply with the EU
                        <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.ENG" target="_GDPR">General Data Protection Regulation (GDPR)</a> or even higher data protection standards. 
			Still, in particular the enforceability of data protection rights in the operator&lsquo;s jurisdiction might deviate from EU/EEA-standards.
                    </p>';?>
                </div>
            </div>

            <form id="ApproveForm" method="post">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="agree_checkbox" name="agree_checkbox">
                        <label class="custom-control-label" for="agree_checkbox">
                            <b>I hereby explicitly consent that the Authorization Server - this service - forwards the personal
                                data in the scope determined by the operator of the application and in accordance with the
                                <a href="<?php print $payload['policy_uri']?>" target="_PS">Privacy Statement</a> to the operator of this application.</b>
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger" role="alert" id="agree_error" style="<?php (isset($payload['agree_privacy_message'])) ? print 'display: block;' : print 'display: none;'?>">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="client_id" value="<?php print $payload['client_id']?>">
                    <input type="hidden" name="scope" value="<?php print $payload['scope']?>">
                    <input type="hidden" id="csrf" name="csrf" value="<?php print $payload['csrf']?>">
                    <input type="hidden" id="authorized" name="authorized" value="No">
                    <input type="hidden" id="agree_privacy" name="agree_privacy" value="No">

                    <div class="row" id="approval-buttons">
			<div class="col-11 offset-1">
                            <input type="submit" id="register_button" value="Yes" class="btn btn-success">
                            <input type="button" onclick="self.close()" id="cancel_button" value="No" class="btn btn-danger">
                        </div>
                    </div>
                </div>
            </form>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row text-center">
                <div class="col-sm-6 offset-sm-3 col-md-3 offset-md-0">
                    <small>&copy <?php print date('Y')?> <a href="https://www.secure-dimensions.de" target="_SD">Secure Dimensions GmbH</a></small>
                </div>
                <div class="col-sm-4 col-md-2">
                    <a href="/TermsOfUse" target="_S">Terms Of Use</a>
                </div>
                <div class="col-sm-4 col-md-2">
                    <a href="/PrivacyStatement" target="_S">Privacy Statement</a>
                </div>
                <div class="col-sm-4 col-md-2">
                    <a href="/CookieStatement" target="_S">Cookie Statement</a>
                </div>
                <div class="col-sm-6 offset-sm-3 col-md-3 offset-md-0">
                    <small>Last updated 07.02.2019</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js" integrity="sha384-rZfj/ogBloos6wzLGpPkkOr/gpkBNLZ6b6yLy4o+ok+t/SAKlL5mvXLr0OXNi1Hp" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script type="text/javascript">

        $(document).ready(function () {
            $("#ApproveForm").validate({
                rules: {
                    agree_checkbox: "required"
                },
                messages: {
                    agree_checkbox: "Please accept our data policy."
                },
                errorElement: "div",
                errorLabelContainer: "#agree_error",
                submitHandler: function (form) {
                    // Set the hidden field content to YES
                    $("#agree_privacy").val("Yes");
                    $("#authorized").val("Yes");

                    // Disable all the controls on the page after submit was pressed
                    $("#agree_checkbox").addClass("disabled").prop("disabled", true);
                    $("#register_button").addClass("disabled").prop("disabled", true);
                    $("#cancel_button").addClass("disabled").prop("disabled", true);

                    // Finally submit the form
                    form.submit();
                }
            });
        });
    </script>
    </div>
</body>

</html>
