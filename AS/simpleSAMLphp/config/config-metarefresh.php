<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(

	'sets' => array(

		'dfn' => array(
			'cron'		=> array('daily'),
			'sources'	=> array(
				array(
					'src' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml',
					'certificates' => array(
						'dfn-aai.g2.pem'
					),
					'template' => array(
						'tags'	=> array('dfn'),
						'authproc' => array(
							51 => array('class' => 'core:AttributeMap', 'oid2name'),
						),
					),

				),
			),
			'expireAfter' 		=> 60*60*24*4, // Maximum 4 days cache time
			'outputDir' 	=> 'metadata/metafresh-dfn/',

			/*
			 * Which output format the metadata should be saved as.
			 * Can be 'flatfile' or 'serialize'. 'flatfile' is the default.
			 */
			'outputFormat' => 'flatfile',
		),
                'eduGain' => array(
                        'cron'          => array('daily'),
                        'sources'       => array(
                                array(
                                        'src' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml',
                                        'template' => array(
                                                'tags'  => array('eduGain'),
                                                'authproc' => array(
                                                        51 => array('class' => 'core:AttributeMap', 'oid2name'),
                                                ),
                                        ),

                                ),
                        ),
                        'expireAfter'           => 60*60*24*4, // Maximum 4 days cache time
                        'outputDir'     => 'metadata/metafresh-eduGain/',

                        /*
                         * Which output format the metadata should be saved as.
                         * Can be 'flatfile' or 'serialize'. 'flatfile' is the default.
                         */
                        'outputFormat' => 'flatfile',
                ),
	),
);
