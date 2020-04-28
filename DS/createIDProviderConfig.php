<?php 

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if (count($argv) != 2)
{
	echo "Usage: $argv[0] <SAML2 metadata file>" . PHP_EOL;
	die;
}

$registrarMap = array(
			'https://www.aai.arn.dz/' => 'ARNaai',
			'http://www.federacionmate.gob.ar' => 'MATE',
			'https://aai.asnet.am' => 'AFIRE',
			'https://aaf.edu.au' => 'AAF',
			'http://eduid.at' => 'ACOnet Identity Federation',
			'https://febas.basnet.by' => 'FEBAS',
			'http://federation.belnet.be/' => 'Belnet Federation',
			'http://cafe.rnp.br' => 'CAFe',
			'http://www.canarie.ca' => 'Canadian Access Federation',
			'http://cofre.reuna.cl' => 'COFRe',
			'http://colfire.co' => 'COLFIRE',
			'http://www.srce.hr' => 'AAI@EduHr',
			'http://www.eduid.cz/' => 'eduID.cz',
			'https://www.wayf.dk' => 'WAYF',
			'https://minga.cedia.org.ec' => 'MINGA',
			'http://taat.edu.ee' => 'TAAT',
			'http://www.csc.fi/haka' => 'HAKA',
			'https://federation.renater.fr/' => 'Fédération Éducation-Recherche',
			'https://mtd.gif.grena.ge' => 'Grena Identity Federation',
			'https://www.aai.dfn.de' => 'DFN AAI',
			'http://aai.grnet.gr/' => 'GRNET',
			'https://hkaf.edu.hk' => 'HKAF',
			'http://eduid.hu' => 'eduId.hu',
			'http://inflibnet.ac.in' => 'INFED',
			'https://irfed.ir/' => 'IR Fed',
			'http://www.heanet.ie' => 'Edugate',
			'http://iif.iucc.ac.il' => 'IUCC Identity Federation',
			'http://www.idem.garr.it/' => 'IDEM',
			'https://www.gakunin.jp' => 'GakuNin',
			'http://kafe.kreonet.net' => 'KAFE',
			'http://laife.lanet.lv/' => 'LAIFE',
			'https://fedi.litnet.lt' => 'LITNET FEDI',
			'http://eduid.lu' => 'eduID Luxembourg',
			'https://rr.aaiedu.mk' => 'AAIEduMk',
			'http://federations.renam.md/' => 'LEAF',
			'http://feide.no/' => 'FEIDE',
			'https://home.trc.gov.om' => 'Oman KID',
			'https://aai.pionier.net.pl' => 'PIONIER.Id',
			'https://www.fccn.pt' => 'RCTSaai',
			'https://www.singaren.net.sg' => 'Singapore Access Federation - SGAF',
			'http://aai.arnes.si' => 'ArnesAAI Slovenska izobraževalno raziskovalna federacija',
			'https://safire.ac.za' => 'SAFIRE',
			'http://www.rediris.es/' => 'SIR',
			'http://www.swamid.se/' => 'SWAMID',
			'http://rr.aai.switch.ch/' => 'SWITCHaai',
			'http://www.surfconext.nl/' => 'SURFconext',
			'https://incommon.org' => 'InCommon',
			'https://www.renu.ac.ug' => 'RIF',
			'https://peano.uran.ua' => 'PEANO',
			'http://ukfederation.org.uk' => 'UK federation',
			'http://gridp.garr.it' => 'Grid Identity Pool'
		);

$metadataFile = $argv[1];

        if(!file_exists($metadataFile)){
                $errorMsg = 'File '.$metadataFile." does not exist";
                echo $errorMsg."\n";
		die;
        }

        if(!is_readable($metadataFile)){
                $errorMsg = 'File '.$metadataFile." cannot be read due to insufficient permissions";
                echo $errorMsg."\n";
		die;
        }

        $CurrentXMLReaderNode = new XMLReader();
        if(!$CurrentXMLReaderNode->open($metadataFile, null, LIBXML_PARSEHUGE | LIBXML_NOERROR | LIBXML_NOWARNING | 1)){
                $errorMsg = 'Could not parse metadata file '.$metadataFile;
                echo $errorMsg."\n";
		die;
        }

        // Go to first element and check it is named 'EntitiesDescriptor'
        // If not it's probably not a valid SAML metadata file
        $CurrentXMLReaderNode->read();
        if ($CurrentXMLReaderNode->localName  !== 'EntitiesDescriptor') {
                $errorMsg = 'Metadata file '.$metadataFile.' does not include a root node EntitiesDescriptor';
                echo $errorMsg."\n";
		die;
        }

	$categories = array();
	$categoryCount = 0;
	$entityCount = 0;
        // Process individual EntityDescriptors
        while( $CurrentXMLReaderNode->read() ) {
                if($CurrentXMLReaderNode->nodeType == XMLReader::ELEMENT && $CurrentXMLReaderNode->localName  === 'EntityDescriptor') {
                        $entityID = $CurrentXMLReaderNode->getAttribute('entityID');
			//echo $entityID . PHP_EOL;

                        $EntityDescriptorXML = $CurrentXMLReaderNode->readOuterXML();
                        $EntityDescriptorDOM = new DOMDocument();
                        $EntityDescriptorDOM->loadXML($EntityDescriptorXML);

			if ($EntityDescriptorDOM->getElementsByTagName('IDPSSODescriptor')->length === 0) {
				continue;
			}
			$nodes=$EntityDescriptorDOM->getElementsByTagName('RegistrationInfo') ;
			if ($nodes->length == 0) {
				if (!isset($categories['unknown'])) {
					$categories['unknown'] = array();
					$categoryCount += 1;
				}
				array_push($categories['unknown'], $entityID);
				$entityCount += 1;
			} 
			else {
				$registrationAuthority = $nodes->item(0)->getAttribute('registrationAuthority');
				//echo "category: " . $registrationAuthority . PHP_EOL;
				if (!isset($categories[$registrationAuthority])) {
					$categories[$registrationAuthority] = array();
					$categoryCount += 1;
				}
				array_push($categories[$registrationAuthority], $entityID);
				$entityCount += 1;
			}
                }
        }
	echo "<?php // Copyright (c) 2018 Secure Dimensions GmbH" . PHP_EOL;
	echo "//Configuration created automatically" . PHP_EOL;
	echo "//number of processed categories: $categoryCount" . PHP_EOL;
	echo "//number of processed entities: $entityCount" . PHP_EOL;

	echo "// Category SDDI is first. SDDI entities do not have a registration authority so we need to print this manually" . PHP_EOL;
	echo "\$IDProviders['sddi'] = array ( 'Type' => 'category', 'Name' => 'SDDI');" . PHP_EOL;
	echo PHP_EOL;

	foreach ($categories as $category => $entityIDs) {
		//echo $category . PHP_EOL;

		if ($category === 'unknown') {
			echo "\$IDProviders['unknown'] = array ( 'Type' => 'category', 'Name' => 'Others', 'de' => array ('Name' => 'Andere'), 'fr' => array ('Name' => 'Autres'), 'it' => array ('Name' => 'Altri'));" . PHP_EOL;
		}
		else {
			$name = (array_key_exists($category, $registrarMap)) ? $registrarMap[$category] : $category;
			echo "\$IDProviders['$category'] = array ( 'Type' => 'category', 'Name' => '$name');" . PHP_EOL;
		}
		foreach ($entityIDs as $entityID) {
			echo "//entityID: " . $entityID. PHP_EOL;
			echo "\$IDProviders['$entityID'] = array( 'Type' => '$category');" . PHP_EOL;
		}
		echo PHP_EOL;
	}
	echo "//Overwrite the SDDI IdPs as they do not have any registration info and would be listed as 'others' otherwise" . PHP_EOL;
	echo "\$IDProviders['<entityID of the Google IdP>'] = array( 'Type' => 'sddi');" . PHP_EOL;
	echo "\$IDProviders['https://tumidp.lrz.de/idp/shibboleth'] = array( 'Type' => 'sddi');" . PHP_EOL;

	echo "?>" . PHP_EOL;
?>
