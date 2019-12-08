<?php
error_reporting(E_ALL & ~E_NOTICE); // report all errors except notices
echo '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="convert csv2vcf" />
	<meta name="keywords" content="csv2vcf" />
	<title>convert csv2vcf</title>
	<!-- external styles -->
	<link href="css/style.css" type="text/css" rel="stylesheet"/>
	</head>
		<body>		
			<div class="boxShadows" id="headline">
				<h1>csv2vcf: convert *.csv to *.vcf</h1>
';
/* === examples for different vCard Versions according to Wikpedia ===
vCard 2.1

BEGIN:VCARD
VERSION:2.1
N:Gump;Forrest;;Mr.
FN:Forrest Gump
ORG:Bubba Gump Shrimp Co.
TITLE:Shrimp Man
PHOTO;GIF:http://www.example.com/dir_photos/my_photo.gif
TEL;WORK;VOICE:(111) 555-1212
TEL;HOME;VOICE:(404) 555-1212
ADR;WORK;PREF:;;100 Waters Edge;Baytown;LA;30314;United States of America
LABEL;WORK;PREF;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:100 Waters Edge=0D=
 =0ABaytown\, LA 30314=0D=0AUnited States of America
ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America
LABEL;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:42 Plantation St.=0D=0A=
 Baytown, LA 30314=0D=0AUnited States of America
EMAIL:forrestgump@example.com
REV:20080424T195243Z
END:VCARD

vCard 3.0

BEGIN:VCARD
VERSION:3.0
N:Gump;Forrest;;Mr.
FN:Forrest Gump
ORG:Bubba Gump Shrimp Co.
TITLE:Shrimp Man
PHOTO;VALUE=URI;TYPE=GIF:http://www.example.com/dir_photos/my_photo.gif
TEL;TYPE=WORK,VOICE:(111) 555-1212
TEL;TYPE=HOME,VOICE:(404) 555-1212
ADR;TYPE=WORK,PREF:;;100 Waters Edge;Baytown;LA;30314;United States of Amer
 ica
LABEL;TYPE=WORK,PREF:100 Waters Edge\nBaytown\, LA 30314\nUnited States of 
 America
ADR;TYPE=HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America
LABEL;TYPE=HOME:42 Plantation St.\nBaytown\, LA 30314\nUnited States of Ame
 rica
EMAIL:forrestgump@example.com
REV:2008-04-24T19:52:43Z
END:VCARD

vCard 4.0

BEGIN:VCARD
VERSION:4.0
N:Forrest;Gump;;Mr.;
FN:Forrest Gump
ORG:Bubba Gump Shrimp Co.
TITLE:Shrimp Man
PHOTO;MEDIATYPE=image/gif:http://www.example.com/dir_photos/my_photo.gif
TEL;TYPE=work,voice;VALUE=uri:tel:+1-111-555-1212
TEL;TYPE=home,voice;VALUE=uri:tel:+1-404-555-1212
ADR;TYPE=work;LABEL="100 Waters Edge\nBaytown, LA 30314\nUnited States of A
 merica";PREF=1:;;100 Waters Edge;Baytown;LA;30314;United States of America
ADR;TYPE=home;LABEL="42 Plantation St.\nBaytown, LA 30314\nUnited States of
 America":;;42 Plantation St.;Baytown;LA;30314;United States of America
EMAIL:forrestgump@example.com
REV:20080424T195243Z
END:VCARD
 */

if(isset($_REQUEST["convert"])) // detect upload of file
{
	$allowedExts = array("csv"); // "gif", "jpeg", "jpg", "png"
	$maximumFileSizeInKBytes = 4096;

	$allowedExts_string = "";
	$target = count($allowedExts);
	for($i=0;$i<$target;$i++)
	{
		$allowedExts_string .= "*.".$allowedExts[$i].", ";
		$allowedExts[] = "text/".$allowedExts[$i];
	}
	
	$maximumFileSizeInBytes = $maximumFileSizeInKBytes * 1024;
	
	if($_FILES)
	{
		if(checkExtension($_FILES["file"]["type"]))
		{
			if(($_FILES["file"]["size"] < $maximumFileSizeInBytes))
			{
				if ($_FILES["file"]["error"] > 0)
				{
					echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
				}
				else
				{
					echo '<div id="uploadDetails">';
					echo "Name: ".$_FILES["file"]["name"]."<br>";
					echo "Type: " . $_FILES["file"]["type"] . "<br>";
					echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
					echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
					convert($_FILES["file"]["tmp_name"]);
					echo '</div>';
				}
			}
			else
			{
				echo "File exceeds filezie limit of ".$maximumFileSizeInBytes."kByte.";
			}
		}
		else
		{
			echo "File was not a allowed filetypes: ".$allowedExts_string;
		}
	}
}
else
{
	echo '

			<form action="csv2vcf_v2.php" method="post" enctype="multipart/form-data">
				<label for="file">Please select your File.csv then hit convert:</label>
				<input type="file" name="file" id="file" style="width: 100%;">
				<br>
				<input type="submit" name="convert" value="convert" style="width: 100%;">
			</form>
			
			<p>the following fields in the *.csv will be converted:</p>
			<div style="overflow:auto;white-space:nowrap;width:100%;" class="codecolorer-container bash default">
				<pre>
display name
street0
city0
region0
postcode0
phone0
phone1
phone2
phone3
email0
organistion0
			</pre>
			</div>
		</div>
	';
}

function checkExtension($ext)
{
	$upload_filename = $_FILES["file"]["name"];
	$upload_filename_array = explode(".", $upload_filename);
	$extension = end($upload_filename_array);

	$result = false;
	global $allowedExts;
	if(in_array($_FILES["file"]["type"], $allowedExts))
	{
		if(in_array($extension, $allowedExts))
		{
			$result = true;
		}
		else
		{
			$result = false;
		}
	}

	return $result;
}


function convert($filename_csv)
{
	// $filename_csv = "export2016.06.24.csv";
	
	$lines_csv = file($filename_csv);
	
	$filename_extension = explode('.',$filename_csv);
	
	$filename_vcf = "./upload/".$_FILES["file"]["name"].".vcf";
	
	$file_vcf = fopen($filename_vcf, 'w') or die("can't open file");
	
	echo '<pre>generating vCard-Version: VERSION:2.1</pre>';
	
	// display name;phone0;phone1;phone2;phone3;phone4;phone5;phone6;phone7;phone8;phone9;phone10;phone11;phone12;phone13;phone14;phone15;phone16;phone17;phone18;phone19;
	// email0;email_type0;email_label0;email1;email_type1;email_label1;email2;email_type2;email_label2;email3;email_type3;email_label3;email4;email_type4;email_label4;email5;email_type5;email_label5;email6;email_type6;email_label6;email7;email_type7;email_label7;email8;email_type8;email_label8;email9;email_type9;email_label9;email10;email_type10;email_label10;email11;email_type11;email_label11;email12;email_type12;email_label12;email13;email_type13;email_label13;
	// pobox0;street0;city0;region0;postcode0;country0;type0;pobox1;
	// street1;city1;region1;postcode1;country1;type1;pobox2;street2;city2;region2;postcode2;country2;type2;pobox3;street3;city3;region3;postcode3;country3;type3;pobox4;street4;city4;region4;postcode4;country4;type4;pobox5;street5;city5;region5;postcode5;country5;type5;pobox6;street6;city6;region6;postcode6;country6;type6;pobox7;street7;city7;region7;postcode7;country7;type7;pobox8;street8;city8;region8;postcode8;country8;type8;pobox9;street9;city9;region9;postcode9;country9;type9;pobox10;street10;city10;region10;postcode10;country10;type10;pobox11;street11;city11;region11;postcode11;country11;type11;pobox12;street12;city12;region12;postcode12;country12;type12;pobox13;street13;city13;region13;postcode13;country13;type13;organistion0;title0;organistion1;title1;organistion2;title2;organistion3;title3;
	
	// ==== fields to match ====
	// display name
	// street0 city0 region0 postcode0
	// phone0 phone1 phone2 phone3
	// email0
	// organistion0
	
	// get all possible fields in android-vcf
	$length = count($lines_csv);
	for($i = 0;$i < $length;$i++)
	{
		if($i == 0)
		{
			$keys_csv = $lines_csv[$i];
			$keys_csv = explode(";",$keys_csv);
		}
		else
		{
			fwrite($file_vcf, "BEGIN:VCARD\n"); // what Version does this file have
			fwrite($file_vcf, "VERSION:2.1\n"); // what Version does this file have
	
			$values_csv = $lines_csv[$i];
			$values_csv = explode(";",$values_csv);
	
			$position = findPos("display name", $keys_csv);
			fwrite($file_vcf, "N:".$values_csv[$position]."\n"); // N:Gump;Forrest;;Mr.
			
			$position = findPos("display name", $keys_csv);
			fwrite($file_vcf, "FN:".$values_csv[$position]."\n"); // FN:Forrest Gump
			
			// ORG:Bubba Gump Shrimp Co.
			// TITLE:Shrimp Man
			// PHOTO;GIF:http://www.example.com/dir_photos/my_photo.gif
			
			// if phone1 differs from phone0
			$position = findPos("phone0", $keys_csv);
			$phone0 = $values_csv[$position];
			fwrite($file_vcf, "TEL;CELL:".$phone0."\n"); // phone0
			
			$position = findPos("phone1", $keys_csv);
			$phone1 = $values_csv[$position];
			if($phone0 != $phone1)
			{
				fwrite($file_vcf, "TEL;CELL:".$phone1."\n"); // phone1
			}
			$position = findPos("phone2", $keys_csv);
			$phone2 = $values_csv[$position];
			if($phone0 != $phone2)
			{
				fwrite($file_vcf, "TEL;CELL:".$phone2."\n"); // phone2
			}
			$position = findPos("phone3", $keys_csv);
			$phone3 = $values_csv[$position];
			if($phone0 != $phone3)
			{
				fwrite($file_vcf, "TEL;CELL:".$phone3."\n"); // phone3
			}

			// ADR;WORK;PREF:;;100 Waters Edge;Baytown;LA;30314;United States of America
			$street0 = $values_csv[findPos("street0", $keys_csv)];
			$city0 = $values_csv[findPos("city0", $keys_csv)];
			$region0 = $values_csv[findPos("region0", $keys_csv)];
			$postcode0 = $values_csv[findPos("postcode0", $keys_csv)];
			$country0 = $values_csv[findPos("country0", $keys_csv)];
			
			fwrite($file_vcf, "ADR;HOME:".$street0.";".$city0.";".$region0.";".$postcode0.";".$country0."\n");
	
			// LABEL;WORK;PREF;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:100 Waters Edge=0D==0 ABaytown\, LA 30314=0D=0AUnited States of America
			// ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America
			// LABEL;HOME;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:42 Plantation St.=0D=0A=
			// Baytown, LA 30314=0D=0AUnited States of America
	
			// EMAIL:forrestgump@example.com
			$position = findPos("email0", $keys_csv);
			fwrite($file_vcf, "EMAIL;HOME:".$values_csv[$position]."\n"); // email0 // EMAIL:
			// REV:20080424T195243Z
			
			fwrite($file_vcf, "END:VCARD"."\n"); // END:VCARD
		}
	}
	
	fclose($file_vcf);

	// move_uploaded_file($_FILES["file"]["tmp_name"],"upload/".$_FILES["file"]["name"]);

	echo "<pre> ".$length." entries transformed</pre>";
	
	echo '<span style="color: green; font-weight: bold;">Please DOWNLOAD RESULT: <a href="'.$filename_vcf.'">'.$filename_vcf.'</a></span>'; // $_FILES["file"]["name"]

	echo '<h1><a href="csv2vcf_v2.php">Do it again</a></h1>';
	// header("Content-type: text/plain");
	// header('Content-Disposition: attachment; filename="'.$filename_vcf.'"');

	/*
	echo '<h1>The result will selfdestruct in 60 seconds</h1>';
	sleep(60);
	
	if(!unlink($filename_vcf))
	{
		echo ("Error deleting ".$filename_vcf);
	}
	else
	{
		echo ("Deleted ".$filename_vcf);
	}
	*/
}

/* determine position of a value in an number-indexed array */
function findPos($value,$array)
{
	$result = null;
	$length = count($array);
	for($i=0;$i<$length;$i++)
	{
		if($array[$i] == $value)
		{
			$result = $i;
			break;
		}
	}
	
	return $result;
}

echo '
		</div>
		<div class="boxShadows" id="headline">
		<p>EXAMPLE INPUT: this goes in:</p>
			<div style="overflow:auto;white-space:nowrap;width:100%;" class="codecolorer-container bash default">
				<pre>
display name;phone0;phone1;phone2;phone3;phone4;phone5;phone6;phone7;phone8;phone9;phone10;phone11;phone12;phone13;phone14;phone15;phone16;phone17;phone18;phone19;email0;email_type0;email_label0;email1;email_type1;email_label1;email2;email_type2;email_label2;email3;email_type3;email_label3;email4;email_type4;email_label4;email5;email_type5;email_label5;email6;email_type6;email_label6;email7;email_type7;email_label7;email8;email_type8;email_label8;email9;email_type9;email_label9;email10;email_type10;email_label10;email11;email_type11;email_label11;email12;email_type12;email_label12;email13;email_type13;email_label13;pobox0;street0;city0;region0;postcode0;country0;type0;pobox1;street1;city1;region1;postcode1;country1;type1;pobox2;street2;city2;region2;postcode2;country2;type2;pobox3;street3;city3;region3;postcode3;country3;type3;pobox4;street4;city4;region4;postcode4;country4;type4;pobox5;street5;city5;region5;postcode5;country5;type5;pobox6;street6;city6;region6;postcode6;country6;type6;pobox7;street7;city7;region7;postcode7;country7;type7;pobox8;street8;city8;region8;postcode8;country8;type8;pobox9;street9;city9;region9;postcode9;country9;type9;pobox10;street10;city10;region10;postcode10;country10;type10;pobox11;street11;city11;region11;postcode11;country11;type11;pobox12;street12;city12;region12;postcode12;country12;type12;pobox13;street13;city13;region13;postcode13;country13;type13;organistion0;title0;organistion1;title1;organistion2;title2;organistion3;title3;
ADAC Pannenhilfe;004-989-222222;004-989-222222;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
ADAC kranken verletzungen;004-989-767676;004-989-767676;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
ADAC-Pannennotruf;+49177222222;+49177222222;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
				</pre>
			</div>
		</div>
		<div class="boxShadows" id="headline">
		<p>this comes out: (this is what android contact book understands)</p>
			<div style="overflow:auto;white-space:nowrap;width:100%;" class="codecolorer-container bash default">
				<pre>
BEGIN:VCARD
VERSION:2.1
N:ADAC Pannenhilfe
FN:ADAC Pannenhilfe
TEL;CELL:004-989-222222
TEL;CELL:
TEL;CELL:
ADR;HOME:;;;;
EMAIL;HOME:
END:VCARD
BEGIN:VCARD
VERSION:2.1
N:ADAC kranken verletzungen
FN:ADAC kranken verletzungen
TEL;CELL:004-989-767676
TEL;CELL:
TEL;CELL:
ADR;HOME:;;;;
EMAIL;HOME:
END:VCARD
BEGIN:VCARD
VERSION:2.1
N:ADAC-Pannennotruf
FN:ADAC-Pannennotruf
TEL;CELL:+49177222222
TEL;CELL:
TEL;CELL:
ADR;HOME:;;;;
EMAIL;HOME:
END:VCARD
				</pre>
			</div>
		</div>
	</body>
</html>
';
?>