<?php

/**
 * CONTROLLER FileHandler
 *
 * @category        framework
 *
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 *
 * @since 			08.05.2018
 *
 * @copyright 		Copyright (C) 2018 - All rights reserved
 *
 * @platform        PHP
 *
 * @requirements    PHP 7.0 or higher
 */

namespace framework\file;

use App\Exceptions\LegacyDieException;
use finfo;
use framework\DBConnector;
use framework\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * UPLOAD_MOD_XSENDFILE and UPLOAD_DISK_PATH has to be defined globally
 */
class FileHandler
{
    /**
     * contains the database connection
     */
    protected $db;

    private static $mimeMap = [
        'application/andrew-inset' => 'ez',
        'application/applixware' => 'aw',
        'application/atom+xml' => 'atom',
        'application/atomcat+xml' => 'atomcat',
        'application/atomsvc+xml' => 'atomsvc',
        'application/bdoc' => 'bdoc',
        'application/ccxml+xml' => 'ccxml',
        'application/cdmi-capability' => 'cdmia',
        'application/cdmi-container' => 'cdmic',
        'application/cdmi-domain' => 'cdmid',
        'application/cdmi-object' => 'cdmio',
        'application/cdmi-queue' => 'cdmiq',
        'application/cu-seeme' => 'cu',
        'application/dash+xml' => 'mpd',
        'application/davmount+xml' => 'davmount',
        'application/docbook+xml' => 'dbk',
        'application/dssc+der' => 'dssc',
        'application/dssc+xml' => 'xdssc',
        'application/ecmascript' => 'ecma',
        'application/emma+xml' => 'emma',
        'application/epub+zip' => 'epub',
        'application/exi' => 'exi',
        'application/font-tdpfr' => 'pfr',
        'application/font-woff' => 'woff',
        'application/font-woff2' => 'woff2',
        'application/gml+xml' => 'gml',
        'application/gpx+xml' => 'gpx',
        'application/gxf' => 'gxf',
        'application/hyperstudio' => 'stk',
        'application/inkml+xml' => ['ink', 'inkml'],
        'application/ipfix' => 'ipfix',
        'application/java-archive' => ['jar', 'war', 'ear'],
        'application/java-serialized-object' => 'ser',
        'application/java-vm' => 'class',
        'application/javascript' => 'js',
        'application/json' => ['json', 'map'],
        'application/json5' => 'json5',
        'application/jsonml+json' => 'jsonml',
        'application/ld+json' => 'jsonld',
        'application/lost+xml' => 'lostxml',
        'application/mac-binhex40' => 'hqx',
        'application/mac-compactpro' => 'cpt',
        'application/mads+xml' => 'mads',
        'application/manifest+json' => 'webmanifest',
        'application/marc' => 'mrc',
        'application/marcxml+xml' => 'mrcx',
        'application/mathematica' => ['ma', 'nb', 'mb'],
        'application/mathml+xml' => 'mathml',
        'application/mbox' => 'mbox',
        'application/mediaservercontrol+xml' => 'mscml',
        'application/metalink+xml' => 'metalink',
        'application/metalink4+xml' => 'meta4',
        'application/mets+xml' => 'mets',
        'application/mods+xml' => 'mods',
        'application/mp21' => ['m21', 'mp21'],
        'application/mp4' => ['mp4s', 'm4p'],
        'application/msword' => ['doc', 'dot'],
        'application/mxf' => 'mxf',
        'application/octet-stream' => 'bin', 'dms', 'lrf', 'mar', 'so', 'dist', 'distz', 'pkg', 'bpk', 'dump', 'elc', 'deploy', 'exe', 'dll', 'deb', 'dmg', 'iso', 'img', 'msi', 'msp', 'msm', 'buffer',
        'application/oda' => 'oda',
        'application/oebps-package+xml' => 'opf',
        'application/ogg' => 'ogx',
        'application/omdoc+xml' => 'omdoc',
        'application/onenote' => ['onetoc', 'onetoc2', 'onetmp', 'onepkg'],
        'application/oxps' => 'oxps',
        'application/patch-ops-error+xml' => 'xer',
        'application/pdf' => 'pdf',
        'application/pgp-encrypted' => 'pgp',
        'application/pgp-signature' => ['asc', 'sig'],
        'application/pics-rules' => 'prf',
        'application/pkcs10' => 'p10',
        'application/pkcs7-mime' => 'p7m', 'p7c',
        'application/pkcs7-signature' => 'p7s',
        'application/pkcs8' => 'p8',
        'application/pkix-attr-cert' => 'ac',
        'application/pkix-cert' => 'cer',
        'application/pkix-crl' => 'crl',
        'application/pkix-pkipath' => 'pkipath',
        'application/pkixcmp' => 'pki',
        'application/pls+xml' => 'pls',
        'application/postscript' => ['ai', 'eps', 'ps'],
        'application/prs.cww' => 'cww',
        'application/pskc+xml' => 'pskcxml',
        'application/rdf+xml' => 'rdf',
        'application/reginfo+xml' => 'rif',
        'application/relax-ng-compact-syntax' => 'rnc',
        'application/resource-lists+xml' => 'rl',
        'application/resource-lists-diff+xml' => 'rld',
        'application/rls-services+xml' => 'rs',
        'application/rpki-ghostbusters' => 'gbr',
        'application/rpki-manifest' => 'mft',
        'application/rpki-roa' => 'roa',
        'application/rsd+xml' => 'rsd',
        'application/rss+xml' => 'rss',
        'application/rtf' => 'rtf',
        'application/sbml+xml' => 'sbml',
        'application/scvp-cv-request' => 'scq',
        'application/scvp-cv-response' => 'scs',
        'application/scvp-vp-request' => 'spq',
        'application/scvp-vp-response' => 'spp',
        'application/sdp' => 'sdp',
        'application/set-payment-initiation' => 'setpay',
        'application/set-registration-initiation' => 'setreg',
        'application/shf+xml' => 'shf',
        'application/smil+xml' => 'smi', 'smil',
        'application/sparql-query' => 'rq',
        'application/sparql-results+xml' => 'srx',
        'application/srgs' => 'gram',
        'application/srgs+xml' => 'grxml',
        'application/sru+xml' => 'sru',
        'application/ssdl+xml' => 'ssdl',
        'application/ssml+xml' => 'ssml',
        'application/tei+xml' => ['tei', 'teicorpus'],
        'application/thraud+xml' => 'tfi',
        'application/timestamped-data' => 'tsd',
        'application/vnd.3gpp.pic-bw-large' => 'plb',
        'application/vnd.3gpp.pic-bw-small' => 'psb',
        'application/vnd.3gpp.pic-bw-var' => 'pvb',
        'application/vnd.3gpp2.tcap' => 'tcap',
        'application/vnd.3m.post-it-notes' => 'pwn',
        'application/vnd.accpac.simply.aso' => 'aso',
        'application/vnd.accpac.simply.imp' => 'imp',
        'application/vnd.acucobol' => 'acu',
        'application/vnd.acucorp' => ['atc', 'acutc'],
        'application/vnd.adobe.air-application-installer-package+zip' => 'air',
        'application/vnd.adobe.formscentral.fcdt' => 'fcdt',
        'application/vnd.adobe.fxp' => ['fxp', 'fxpl'],
        'application/vnd.adobe.xdp+xml' => 'xdp',
        'application/vnd.adobe.xfdf' => 'xfdf',
        'application/vnd.ahead.space' => 'ahead',
        'application/vnd.airzip.filesecure.azf' => 'azf',
        'application/vnd.airzip.filesecure.azs' => 'azs',
        'application/vnd.amazon.ebook' => 'azw',
        'application/vnd.americandynamics.acc' => 'acc',
        'application/vnd.amiga.ami' => 'ami',
        'application/vnd.android.package-archive' => 'apk',
        'application/vnd.anser-web-certificate-issue-initiation' => 'cii',
        'application/vnd.anser-web-funds-transfer-initiation' => 'fti',
        'application/vnd.antix.game-component' => 'atx',
        'application/vnd.apple.installer+xml' => 'mpkg',
        'application/vnd.apple.mpegurl' => 'm3u8',
        'application/vnd.apple.pkpass' => 'pkpass',
        'application/vnd.aristanetworks.swi' => 'swi',
        'application/vnd.astraea-software.iota' => 'iota',
        'application/vnd.audiograph' => 'aep',
        'application/vnd.blueice.multipass' => 'mpm',
        'application/vnd.bmi' => 'bmi',
        'application/vnd.businessobjects' => 'rep',
        'application/vnd.chemdraw+xml' => 'cdxml',
        'application/vnd.chipnuts.karaoke-mmd' => 'mmd',
        'application/vnd.cinderella' => 'cdy',
        'application/vnd.claymore' => 'cla',
        'application/vnd.cloanto.rp9' => 'rp9',
        'application/vnd.clonk.c4group' => ['c4g', 'c4d', 'c4f', 'c4p', 'c4u'],
        'application/vnd.cluetrust.cartomobile-config' => 'c11amc',
        'application/vnd.cluetrust.cartomobile-config-pkg' => 'c11amz',
        'application/vnd.commonspace' => 'csp',
        'application/vnd.contact.cmsg' => 'cdbcmsg',
        'application/vnd.cosmocaller' => 'cmc',
        'application/vnd.crick.clicker' => 'clkx',
        'application/vnd.crick.clicker.keyboard' => 'clkk',
        'application/vnd.crick.clicker.palette' => 'clkp',
        'application/vnd.crick.clicker.template' => 'clkt',
        'application/vnd.crick.clicker.wordbank' => 'clkw',
        'application/vnd.criticaltools.wbs+xml' => 'wbs',
        'application/vnd.ctc-posml' => 'pml',
        'application/vnd.cups-ppd' => 'ppd',
        'application/vnd.curl.car' => 'car',
        'application/vnd.curl.pcurl' => 'pcurl',
        'application/vnd.dart' => 'dart',
        'application/vnd.data-vision.rdz' => 'rdz',
        'application/vnd.dece.data' => ['uvf', 'uvvf', 'uvd', 'uvvd'],
        'application/vnd.dece.ttml+xml' => ['uvt', 'uvvt'],
        'application/vnd.dece.unspecified' => ['uvx', 'uvvx'],
        'application/vnd.dece.zip' => ['uvz', 'uvvz'],
        'application/vnd.denovo.fcselayout-link' => 'fe_launch',
        'application/vnd.dna' => 'dna',
        'application/vnd.dolby.mlp' => 'mlp',
        'application/vnd.dpgraph' => 'dpg',
        'application/vnd.dreamfactory' => 'dfac',
        'application/vnd.ds-keypoint' => 'kpxx',
        'application/vnd.dvb.ait' => 'ait',
        'application/vnd.dvb.service' => 'svc',
        'application/vnd.dynageo' => 'geo',
        'application/vnd.ecowin.chart' => 'mag',
        'application/vnd.enliven' => 'nml',
        'application/vnd.epson.esf' => 'esf',
        'application/vnd.epson.msf' => 'msf',
        'application/vnd.epson.quickanime' => 'qam',
        'application/vnd.epson.salt' => 'slt',
        'application/vnd.epson.ssf' => 'ssf',
        'application/vnd.eszigno3+xml' => ['es3', 'et3'],
        'application/vnd.ezpix-album' => 'ez2',
        'application/vnd.ezpix-package' => 'ez3',
        'application/vnd.fdf' => 'fdf',
        'application/vnd.fdsn.mseed' => 'mseed',
        'application/vnd.fdsn.seed' => ['seed', 'dataless'],
        'application/vnd.flographit' => 'gph',
        'application/vnd.fluxtime.clip' => 'ftc',
        'application/vnd.framemaker' => ['fm', 'frame', 'maker', 'book'],
        'application/vnd.frogans.fnc' => 'fnc',
        'application/vnd.frogans.ltf' => 'ltf',
        'application/vnd.fsc.weblaunch' => 'fsc',
        'application/vnd.fujitsu.oasys' => 'oas',
        'application/vnd.fujitsu.oasys2' => 'oa2',
        'application/vnd.fujitsu.oasys3' => 'oa3',
        'application/vnd.fujitsu.oasysgp' => 'fg5',
        'application/vnd.fujitsu.oasysprs' => 'bh2',
        'application/vnd.fujixerox.ddd' => 'ddd',
        'application/vnd.fujixerox.docuworks' => 'xdw',
        'application/vnd.fujixerox.docuworks.binder' => 'xbd',
        'application/vnd.fuzzysheet' => 'fzs',
        'application/vnd.genomatix.tuxedo' => 'txd',
        'application/vnd.geogebra.file' => 'ggb',
        'application/vnd.geogebra.tool' => 'ggt',
        'application/vnd.geometry-explorer' => ['gex', 'gre'],
        'application/vnd.geonext' => 'gxt',
        'application/vnd.geoplan' => 'g2w',
        'application/vnd.geospace' => 'g3w',
        'application/vnd.gmx' => 'gmx',
        'application/vnd.google-apps.document' => 'gdoc',
        'application/vnd.google-apps.presentation' => 'gslides',
        'application/vnd.google-apps.spreadsheet' => 'gsheet',
        'application/vnd.google-earth.kml+xml' => 'kml',
        'application/vnd.google-earth.kmz' => 'kmz',
        'application/vnd.grafeq' => 'gqf', 'gqs',
        'application/vnd.groove-account' => 'gac',
        'application/vnd.groove-help' => 'ghf',
        'application/vnd.groove-identity-message' => 'gim',
        'application/vnd.groove-injector' => 'grv',
        'application/vnd.groove-tool-message' => 'gtm',
        'application/vnd.groove-tool-template' => 'tpl',
        'application/vnd.groove-vcard' => 'vcg',
        'application/vnd.hal+xml' => 'hal',
        'application/vnd.handheld-entertainment+xml' => 'zmm',
        'application/vnd.hbci' => 'hbci',
        'application/vnd.hhe.lesson-player' => 'les',
        'application/vnd.hp-hpgl' => 'hpgl',
        'application/vnd.hp-hpid' => 'hpid',
        'application/vnd.hp-hps' => 'hps',
        'application/vnd.hp-jlyt' => 'jlt',
        'application/vnd.hp-pcl' => 'pcl',
        'application/vnd.hp-pclxl' => 'pclxl',
        'application/vnd.hydrostatix.sof-data' => 'sfd-hdstx',
        'application/vnd.ibm.minipay' => 'mpy',
        'application/vnd.ibm.modcap' => ['afp', 'listafp', 'list3820'],
        'application/vnd.ibm.rights-management' => 'irm',
        'application/vnd.ibm.secure-container' => 'sc',
        'application/vnd.iccprofile' => 'icc', 'icm',
        'application/vnd.igloader' => 'igl',
        'application/vnd.immervision-ivp' => 'ivp',
        'application/vnd.immervision-ivu' => 'ivu',
        'application/vnd.insors.igm' => 'igm',
        'application/vnd.intercon.formnet' => ['xpw', 'xpx'],
        'application/vnd.intergeo' => 'i2g',
        'application/vnd.intu.qbo' => 'qbo',
        'application/vnd.intu.qfx' => 'qfx',
        'application/vnd.ipunplugged.rcprofile' => 'rcprofile',
        'application/vnd.irepository.package+xml' => 'irp',
        'application/vnd.is-xpr' => 'xpr',
        'application/vnd.isac.fcs' => 'fcs',
        'application/vnd.jam' => 'jam',
        'application/vnd.jcp.javame.midlet-rms' => 'rms',
        'application/vnd.jisp' => 'jisp',
        'application/vnd.joost.joda-archive' => 'joda',
        'application/vnd.kahootz' => 'ktz', 'ktr',
        'application/vnd.kde.karbon' => 'karbon',
        'application/vnd.kde.kchart' => 'chrt',
        'application/vnd.kde.kformula' => 'kfo',
        'application/vnd.kde.kivio' => 'flw',
        'application/vnd.kde.kontour' => 'kon',
        'application/vnd.kde.kpresenter' => ['kpr', 'kpt'],
        'application/vnd.kde.kspread' => 'ksp',
        'application/vnd.kde.kword' => ['kwd', 'kwt'],
        'application/vnd.kenameaapp' => 'htke',
        'application/vnd.kidspiration' => 'kia',
        'application/vnd.kinar' => ['kne', 'knp'],
        'application/vnd.koan' => ['skp', 'skd', 'skt', 'skm'],
        'application/vnd.kodak-descriptor' => 'sse',
        'application/vnd.las.las+xml' => 'lasxml',
        'application/vnd.llamagraphics.life-balance.desktop' => 'lbd',
        'application/vnd.llamagraphics.life-balance.exchange+xml' => 'lbe',
        'application/vnd.lotus-1-2-3' => '123',
        'application/vnd.lotus-approach' => 'apr',
        'application/vnd.lotus-freelance' => 'pre',
        'application/vnd.lotus-notes' => 'nsf',
        'application/vnd.lotus-organizer' => 'org',
        'application/vnd.lotus-screencam' => 'scm',
        'application/vnd.lotus-wordpro' => 'lwp',
        'application/vnd.macports.portpkg' => 'portpkg',
        'application/vnd.mcd' => 'mcd',
        'application/vnd.medcalcdata' => 'mc1',
        'application/vnd.mediastation.cdkey' => 'cdkey',
        'application/vnd.mfer' => 'mwf',
        'application/vnd.mfmp' => 'mfm',
        'application/vnd.micrografx.flo' => 'flo',
        'application/vnd.micrografx.igx' => 'igx',
        'application/vnd.mif' => 'mif',
        'application/vnd.mobius.daf' => 'daf',
        'application/vnd.mobius.dis' => 'dis',
        'application/vnd.mobius.mbk' => 'mbk',
        'application/vnd.mobius.mqy' => 'mqy',
        'application/vnd.mobius.msl' => 'msl',
        'application/vnd.mobius.plc' => 'plc',
        'application/vnd.mobius.txf' => 'txf',
        'application/vnd.mophun.application' => 'mpn',
        'application/vnd.mophun.certificate' => 'mpc',
        'application/vnd.mozilla.xul+xml' => 'xul',
        'application/vnd.ms-artgalry' => 'cil',
        'application/vnd.ms-cab-compressed' => 'cab',
        'application/vnd.ms-excel' => ['xls', 'xlm', 'xla', 'xlc', 'xlt', 'xlw'],
        'application/vnd.ms-excel.addin.macroenabled.12' => 'xlam',
        'application/vnd.ms-excel.sheet.binary.macroenabled.12' => 'xlsb',
        'application/vnd.ms-excel.sheet.macroenabled.12' => 'xlsm',
        'application/vnd.ms-excel.template.macroenabled.12' => 'xltm',
        'application/vnd.ms-fontobject' => 'eot',
        'application/vnd.ms-htmlhelp' => 'chm',
        'application/vnd.ms-ims' => 'ims',
        'application/vnd.ms-lrm' => 'lrm',
        'application/vnd.ms-officetheme' => 'thmx',
        'application/vnd.ms-pki.seccat' => 'cat',
        'application/vnd.ms-pki.stl' => 'stl',
        'application/vnd.ms-powerpoint' => ['ppt', 'pps', 'pot'],
        'application/vnd.ms-powerpoint.addin.macroenabled.12' => 'ppam',
        'application/vnd.ms-powerpoint.presentation.macroenabled.12' => 'pptm',
        'application/vnd.ms-powerpoint.slide.macroenabled.12' => 'sldm',
        'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => 'ppsm',
        'application/vnd.ms-powerpoint.template.macroenabled.12' => 'potm',
        'application/vnd.ms-project' => ['mpp', 'mpt'],
        'application/vnd.ms-word.document.macroenabled.12' => 'docm',
        'application/vnd.ms-word.template.macroenabled.12' => 'dotm',
        'application/vnd.ms-works' => ['wps', 'wks', 'wcm', 'wdb'],
        'application/vnd.ms-wpl' => 'wpl',
        'application/vnd.ms-xpsdocument' => 'xps',
        'application/vnd.mseq' => 'mseq',
        'application/vnd.musician' => 'mus',
        'application/vnd.muvee.style' => 'msty',
        'application/vnd.mynfc' => 'taglet',
        'application/vnd.neurolanguage.nlu' => 'nlu',
        'application/vnd.nitf' => 'ntf', 'nitf',
        'application/vnd.noblenet-directory' => 'nnd',
        'application/vnd.noblenet-sealer' => 'nns',
        'application/vnd.noblenet-web' => 'nnw',
        'application/vnd.nokia.n-gage.data' => 'ngdat',
        'application/vnd.nokia.n-gage.symbian.install' => 'n-gage',
        'application/vnd.nokia.radio-preset' => 'rpst',
        'application/vnd.nokia.radio-presets' => 'rpss',
        'application/vnd.novadigm.edm' => 'edm',
        'application/vnd.novadigm.edx' => 'edx',
        'application/vnd.novadigm.ext' => 'ext',
        'application/vnd.oasis.opendocument.chart' => 'odc',
        'application/vnd.oasis.opendocument.chart-template' => 'otc',
        'application/vnd.oasis.opendocument.database' => 'odb',
        'application/vnd.oasis.opendocument.formula' => 'odf',
        'application/vnd.oasis.opendocument.formula-template' => 'odft',
        'application/vnd.oasis.opendocument.graphics' => 'odg',
        'application/vnd.oasis.opendocument.graphics-template' => 'otg',
        'application/vnd.oasis.opendocument.image' => 'odi',
        'application/vnd.oasis.opendocument.image-template' => 'oti',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
        'application/vnd.oasis.opendocument.presentation-template' => 'otp',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.oasis.opendocument.spreadsheet-template' => 'ots',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.text-master' => 'odm',
        'application/vnd.oasis.opendocument.text-template' => 'ott',
        'application/vnd.oasis.opendocument.text-web' => 'oth',
        'application/vnd.olpc-sugar' => 'xo',
        'application/vnd.oma.dd2+xml' => 'dd2',
        'application/vnd.openofficeorg.extension' => 'oxt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.presentationml.slide' => 'sldx',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'ppsx',
        'application/vnd.openxmlformats-officedocument.presentationml.template' => 'potx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'dotx',
        'application/vnd.osgeo.mapguide.package' => 'mgp',
        'application/vnd.osgi.dp' => 'dp',
        'application/vnd.osgi.subsystem' => 'esa',
        'application/vnd.palm' => ['pdb', 'pqa', 'oprc'],
        'application/vnd.pawaafile' => 'paw',
        'application/vnd.pg.format' => 'str',
        'application/vnd.pg.osasli' => 'ei6',
        'application/vnd.picsel' => 'efif',
        'application/vnd.pmi.widget' => 'wg',
        'application/vnd.pocketlearn' => 'plf',
        'application/vnd.powerbuilder6' => 'pbd',
        'application/vnd.previewsystems.box' => 'box',
        'application/vnd.proteus.magazine' => 'mgz',
        'application/vnd.publishare-delta-tree' => 'qps',
        'application/vnd.pvi.ptid1' => 'ptid',
        'application/vnd.quark.quarkxpress' => ['qxd', 'qxt', 'qwd', 'qwt', 'qxl', 'qxb'],
        'application/vnd.realvnc.bed' => 'bed',
        'application/vnd.recordare.musicxml' => 'mxl',
        'application/vnd.recordare.musicxml+xml' => 'musicxml',
        'application/vnd.rig.cryptonote' => 'cryptonote',
        'application/vnd.rim.cod' => 'cod',
        'application/vnd.rn-realmedia' => 'rm',
        'application/vnd.rn-realmedia-vbr' => 'rmvb',
        'application/vnd.route66.link66+xml' => 'link66',
        'application/vnd.sailingtracker.track' => 'st',
        'application/vnd.seemail' => 'see',
        'application/vnd.sema' => 'sema',
        'application/vnd.semd' => 'semd',
        'application/vnd.semf' => 'semf',
        'application/vnd.shana.informed.formdata' => 'ifm',
        'application/vnd.shana.informed.formtemplate' => 'itp',
        'application/vnd.shana.informed.interchange' => 'iif',
        'application/vnd.shana.informed.package' => 'ipk',
        'application/vnd.simtech-mindmapper' => 'twd', 'twds',
        'application/vnd.smaf' => 'mmf',
        'application/vnd.smart.teacher' => 'teacher',
        'application/vnd.solent.sdkm+xml' => ['sdkm', 'sdkd'],
        'application/vnd.spotfire.dxp' => 'dxp',
        'application/vnd.spotfire.sfs' => 'sfs',
        'application/vnd.stardivision.calc' => 'sdc',
        'application/vnd.stardivision.draw' => 'sda',
        'application/vnd.stardivision.impress' => 'sdd',
        'application/vnd.stardivision.math' => 'smf',
        'application/vnd.stardivision.writer' => ['sdw', 'vor'],
        'application/vnd.stardivision.writer-global' => 'sgl',
        'application/vnd.stepmania.package' => 'smzip',
        'application/vnd.stepmania.stepchart' => 'sm',
        'application/vnd.sun.xml.calc' => 'sxc',
        'application/vnd.sun.xml.calc.template' => 'stc',
        'application/vnd.sun.xml.draw' => 'sxd',
        'application/vnd.sun.xml.draw.template' => 'std',
        'application/vnd.sun.xml.impress' => 'sxi',
        'application/vnd.sun.xml.impress.template' => 'sti',
        'application/vnd.sun.xml.math' => 'sxm',
        'application/vnd.sun.xml.writer' => 'sxw',
        'application/vnd.sun.xml.writer.global' => 'sxg',
        'application/vnd.sun.xml.writer.template' => 'stw',
        'application/vnd.sus-calendar' => ['sus', 'susp'],
        'application/vnd.svd' => 'svd',
        'application/vnd.symbian.install' => ['sis', 'sisx'],
        'application/vnd.syncml+xml' => 'xsm',
        'application/vnd.syncml.dm+wbxml' => 'bdm',
        'application/vnd.syncml.dm+xml' => 'xdm',
        'application/vnd.tao.intent-module-archive' => 'tao',
        'application/vnd.tcpdump.pcap' => ['pcap', 'cap', 'dmp'],
        'application/vnd.tmobile-livetv' => 'tmo',
        'application/vnd.trid.tpt' => 'tpt',
        'application/vnd.triscape.mxs' => 'mxs',
        'application/vnd.trueapp' => 'tra',
        'application/vnd.ufdl' => ['ufd', 'ufdl'],
        'application/vnd.uiq.theme' => 'utz',
        'application/vnd.umajin' => 'umj',
        'application/vnd.unity' => 'unityweb',
        'application/vnd.uoml+xml' => 'uoml',
        'application/vnd.vcx' => 'vcx',
        'application/vnd.visio' => ['vsd', 'vst', 'vss', 'vsw'],
        'application/vnd.visionary' => 'vis',
        'application/vnd.vsf' => 'vsf',
        'application/vnd.wap.wbxml' => 'wbxml',
        'application/vnd.wap.wmlc' => 'wmlc',
        'application/vnd.wap.wmlscriptc' => 'wmlsc',
        'application/vnd.webturbo' => 'wtb',
        'application/vnd.wolfram.player' => 'nbp',
        'application/vnd.wordperfect' => 'wpd',
        'application/vnd.wqd' => 'wqd',
        'application/vnd.wt.stf' => 'stf',
        'application/vnd.xara' => 'xar',
        'application/vnd.xfdl' => 'xfdl',
        'application/vnd.yamaha.hv-dic' => 'hvd',
        'application/vnd.yamaha.hv-script' => 'hvs',
        'application/vnd.yamaha.hv-voice' => 'hvp',
        'application/vnd.yamaha.openscoreformat' => 'osf',
        'application/vnd.yamaha.openscoreformat.osfpvg+xml' => 'osfpvg',
        'application/vnd.yamaha.smaf-audio' => 'saf',
        'application/vnd.yamaha.smaf-phrase' => 'spf',
        'application/vnd.yellowriver-custom-menu' => 'cmp',
        'application/vnd.zul' => ['zir', 'zirz'],
        'application/vnd.zzazz.deck+xml' => 'zaz',
        'application/voicexml+xml' => 'vxml',
        'application/widget' => 'wgt',
        'application/winhlp' => 'hlp',
        'application/wsdl+xml' => 'wsdl',
        'application/wspolicy+xml' => 'wspolicy',
        'application/x-7z-compressed' => '7z',
        'application/x-abiword' => 'abw',
        'application/x-ace-compressed' => 'ace',
        'application/x-apple-diskimage' => 'dmg',
        'application/x-authorware-bin' => ['aab', 'x32', 'u32', 'vox'],
        'application/x-authorware-map' => 'aam',
        'application/x-authorware-seg' => 'aas',
        'application/x-bcpio' => 'bcpio',
        'application/x-bdoc' => 'bdoc',
        'application/x-bittorrent' => 'torrent',
        'application/x-blorb' => ['blb', 'blorb'],
        'application/x-bzip' => 'bz',
        'application/x-bzip2' => ['bz2', 'boz'],
        'application/x-cbr' => ['cbr', 'cba', 'cbt', 'cbz', 'cb7'],
        'application/x-cdlink' => 'vcd',
        'application/x-cfs-compressed' => 'cfs',
        'application/x-chat' => 'chat',
        'application/x-chess-pgn' => 'pgn',
        'application/x-chrome-extension' => 'crx',
        'application/x-cocoa' => 'cco',
        'application/x-conference' => 'nsc',
        'application/x-cpio' => 'cpio',
        'application/x-csh' => 'csh',
        'application/x-debian-package' => ['deb', 'udeb'],
        'application/x-dgc-compressed' => 'dgc',
        'application/x-director' => ['dir', 'dcr', 'dxr', 'cst', 'cct', 'cxt', 'w3d', 'fgd', 'swa'],
        'application/x-doom' => 'wad',
        'application/x-dtbncx+xml' => 'ncx',
        'application/x-dtbook+xml' => 'dtb',
        'application/x-dtbresource+xml' => 'res',
        'application/x-dvi' => 'dvi',
        'application/x-envoy' => 'evy',
        'application/x-eva' => 'eva',
        'application/x-font-bdf' => 'bdf',
        'application/x-font-ghostscript' => 'gsf',
        'application/x-font-linux-psf' => 'psf',
        'application/x-font-otf' => 'otf',
        'application/x-font-pcf' => 'pcf',
        'application/x-font-snf' => 'snf',
        'application/x-font-ttf' => ['ttf', 'ttc'],
        'application/x-font-type1' => ['pfa', 'pfb', 'pfm', 'afm'],
        'application/x-freearc' => 'arc',
        'application/x-futuresplash' => 'spl',
        'application/x-gca-compressed' => 'gca',
        'application/x-glulx' => 'ulx',
        'application/x-gnumeric' => 'gnumeric',
        'application/x-gramps-xml' => 'gramps',
        'application/x-gtar' => 'gtar',
        'application/x-hdf' => 'hdf',
        'application/x-httpd-php' => 'php',
        'application/x-install-instructions' => 'install',
        'application/x-iso9660-image' => 'iso',
        'application/x-java-archive-diff' => 'jardiff',
        'application/x-java-jnlp-file' => 'jnlp',
        'application/x-latex' => 'latex',
        'application/x-lua-bytecode' => 'luac',
        'application/x-lzh-compressed' => ['lzh', 'lha'],
        'application/x-makeself' => 'run',
        'application/x-mie' => 'mie',
        'application/x-mobipocket-ebook' => ['prc', 'mobi'],
        'application/x-ms-application' => 'application',
        'application/x-ms-shortcut' => 'lnk',
        'application/x-ms-wmd' => 'wmd',
        'application/x-ms-wmz' => 'wmz',
        'application/x-ms-xbap' => 'xbap',
        'application/x-msaccess' => 'mdb',
        'application/x-msbinder' => 'obd',
        'application/x-mscardfile' => 'crd',
        'application/x-msclip' => 'clp',
        'application/x-msdos-program' => 'exe',
        'application/x-msdownload' => ['exe', 'dll', 'com', 'bat', 'msi'],
        'application/x-msmediaview' => ['mvb', 'm13', 'm14'],
        'application/x-msmetafile' => ['wmf', 'wmz', 'emf', 'emz'],
        'application/x-msmoney' => 'mny',
        'application/x-mspublisher' => 'pub',
        'application/x-msschedule' => 'scd',
        'application/x-msterminal' => 'trm',
        'application/x-mswrite' => 'wri',
        'application/x-netcdf' => ['nc', 'cdf'],
        'application/x-ns-proxy-autoconfig' => 'pac',
        'application/x-nzb' => 'nzb',
        'application/x-perl' => ['pl', 'pm'],
        'application/x-pilot' => ['prc', 'pdb'],
        'application/x-pkcs12' => ['p12', 'pfx'],
        'application/x-pkcs7-certificates' => ['p7b', 'spc'],
        'application/x-pkcs7-certreqresp' => 'p7r',
        'application/x-rar-compressed' => 'rar',
        'application/x-redhat-package-manager' => 'rpm',
        'application/x-research-info-systems' => 'ris',
        'application/x-sea' => 'sea',
        'application/x-sh' => 'sh',
        'application/x-shar' => 'shar',
        'application/x-shockwave-flash' => 'swf',
        'application/x-silverlight-app' => 'xap',
        'application/x-sql' => 'sql',
        'application/x-stuffit' => 'sit',
        'application/x-stuffitx' => 'sitx',
        'application/x-subrip' => 'srt',
        'application/x-sv4cpio' => 'sv4cpio',
        'application/x-sv4crc' => 'sv4crc',
        'application/x-t3vm-image' => 't3',
        'application/x-tads' => 'gam',
        'application/x-tar' => 'tar',
        'application/x-tcl' => ['tcl', 'tk'],
        'application/x-tex' => 'tex',
        'application/x-tex-tfm' => 'tfm',
        'application/x-texinfo' => ['texinfo', 'texi'],
        'application/x-tgif' => 'obj',
        'application/x-ustar' => 'ustar',
        'application/x-wais-source' => 'src',
        'application/x-web-app-manifest+json' => 'webapp',
        'application/x-x509-ca-cert' => ['der', 'crt', 'pem'],
        'application/x-xfig' => 'fig',
        'application/x-xliff+xml' => 'xlf',
        'application/x-xpinstall' => 'xpi',
        'application/x-xz' => 'xz',
        'application/x-zmachine' => ['z1', 'z2', 'z3', 'z4', 'z5', 'z6', 'z7', 'z8'],
        'application/xaml+xml' => 'xaml',
        'application/xcap-diff+xml' => 'xdf',
        'application/xenc+xml' => 'xenc',
        'application/xhtml+xml' => ['xhtml', 'xht'],
        'application/xml' => ['xml', 'xsl', 'xsd', 'rng'],
        'application/xml-dtd' => 'dtd',
        'application/xop+xml' => 'xop',
        'application/xproc+xml' => 'xpl',
        'application/xslt+xml' => 'xslt',
        'application/xspf+xml' => 'xspf',
        'application/xv+xml' => ['mxml', 'xhvml', 'xvml', 'xvm'],
        'application/yang' => 'yang',
        'application/yin+xml' => 'yin',
        'application/zip' => 'zip',
        'audio/3gpp' => '3gpp',
        'audio/adpcm' => 'adp',
        'audio/basic' => 'au', 'snd',
        'audio/midi' => ['mid', 'midi', 'kar', 'rmi'],
        'audio/mp4' => ['m4a', 'mp4a'],
        'audio/mpeg' => ['mpga', 'mp2', 'mp2a', 'mp3', 'm2a', 'm3a'],
        'audio/ogg' => ['oga', 'ogg', 'spx'],
        'audio/s3m' => 's3m',
        'audio/silk' => 'sil',
        'audio/vnd.dece.audio' => ['uva', 'uvva'],
        'audio/vnd.digital-winds' => 'eol',
        'audio/vnd.dra' => 'dra',
        'audio/vnd.dts' => 'dts',
        'audio/vnd.dts.hd' => 'dtshd',
        'audio/vnd.lucent.voice' => 'lvp',
        'audio/vnd.ms-playready.media.pya' => 'pya',
        'audio/vnd.nuera.ecelp4800' => 'ecelp4800',
        'audio/vnd.nuera.ecelp7470' => 'ecelp7470',
        'audio/vnd.nuera.ecelp9600' => 'ecelp9600',
        'audio/vnd.rip' => 'rip',
        'audio/wav' => 'wav',
        'audio/wave' => 'wav',
        'audio/webm' => 'weba',
        'audio/x-aac' => 'aac',
        'audio/x-aiff' => ['aif', 'aiff', 'aifc'],
        'audio/x-caf' => 'caf',
        'audio/x-flac' => 'flac',
        'audio/x-m4a' => 'm4a',
        'audio/x-matroska' => 'mka',
        'audio/x-mpegurl' => 'm3u',
        'audio/x-ms-wax' => 'wax',
        'audio/x-ms-wma' => 'wma',
        'audio/x-pn-realaudio' => ['ram', 'ra'],
        'audio/x-pn-realaudio-plugin' => 'rmp',
        'audio/x-realaudio' => 'ra',
        'audio/x-wav' => 'wav',
        'audio/xm' => 'xm',
        'chemical/x-cdx' => 'cdx',
        'chemical/x-cif' => 'cif',
        'chemical/x-cmdf' => 'cmdf',
        'chemical/x-cml' => 'cml',
        'chemical/x-csml' => 'csml',
        'chemical/x-xyz' => 'xyz',
        'font/opentype' => 'otf',
        'image/bmp' => 'bmp',
        'image/cgm' => 'cgm',
        'image/g3fax' => 'g3',
        'image/gif' => 'gif',
        'image/ief' => 'ief',
        'image/jpeg' => ['jpeg', 'jpg', 'jpe'],
        'image/ktx' => 'ktx',
        'image/png' => 'png',
        'image/prs.btif' => 'btif',
        'image/sgi' => 'sgi',
        'image/svg+xml' => ['svg', 'svgz'],
        'image/tiff' => ['tiff', 'tif'],
        'image/vnd.adobe.photoshop' => 'psd',
        'image/vnd.dece.graphic' => ['uvi', 'uvvi', 'uvg', 'uvvg'],
        'image/vnd.djvu' => ['djvu', 'djv'],
        'image/vnd.dvb.subtitle' => 'sub',
        'image/vnd.dwg' => 'dwg',
        'image/vnd.dxf' => 'dxf',
        'image/vnd.fastbidsheet' => 'fbs',
        'image/vnd.fpx' => 'fpx',
        'image/vnd.fst' => 'fst',
        'image/vnd.fujixerox.edmics-mmr' => 'mmr',
        'image/vnd.fujixerox.edmics-rlc' => 'rlc',
        'image/vnd.ms-modi' => 'mdi',
        'image/vnd.ms-photo' => 'wdp',
        'image/vnd.net-fpx' => 'npx',
        'image/vnd.wap.wbmp' => 'wbmp',
        'image/vnd.xiff' => 'xif',
        'image/webp' => 'webp',
        'image/x-3ds' => '3ds',
        'image/x-cmu-raster' => 'ras',
        'image/x-cmx' => 'cmx',
        'image/x-freehand' => ['fh', 'fhc', 'fh4', 'fh5', 'fh7'],
        'image/x-icon' => 'ico',
        'image/x-jng' => 'jng',
        'image/x-mrsid-image' => 'sid',
        'image/x-ms-bmp' => 'bmp',
        'image/x-pcx' => 'pcx',
        'image/x-pict' => ['pic', 'pct'],
        'image/x-portable-anymap' => 'pnm',
        'image/x-portable-bitmap' => 'pbm',
        'image/x-portable-graymap' => 'pgm',
        'image/x-portable-pixmap' => 'ppm',
        'image/x-rgb' => 'rgb',
        'image/x-tga' => 'tga',
        'image/x-xbitmap' => 'xbm',
        'image/x-xpixmap' => 'xpm',
        'image/x-xwindowdump' => 'xwd',
        'message/rfc822' => ['eml', 'mime'],
        'model/iges' => ['igs', 'iges'],
        'model/mesh' => ['msh', 'mesh', 'silo'],
        'model/vnd.collada+xml' => 'dae',
        'model/vnd.dwf' => 'dwf',
        'model/vnd.gdl' => 'gdl',
        'model/vnd.gtw' => 'gtw',
        'model/vnd.mts' => 'mts',
        'model/vnd.vtu' => 'vtu',
        'model/vrml' => ['wrl', 'vrml'],
        'model/x3d+binary' => ['x3db', 'x3dbz'],
        'model/x3d+vrml' => ['x3dv', 'x3dvz'],
        'model/x3d+xml' => ['x3d', 'x3dz'],
        'text/cache-manifest' => ['appcache', 'manifest'],
        'text/calendar' => ['ics', 'ifb'],
        'text/coffeescript' => ['coffee', 'litcoffee'],
        'text/css' => 'css',
        'text/csv' => 'csv',
        'text/hjson' => 'hjson',
        'text/html' => ['html', 'htm', 'shtml'],
        'text/jade' => 'jade',
        'text/jsx' => 'jsx',
        'text/less' => 'less',
        'text/mathml' => 'mml',
        'text/n3' => 'n3',
        'text/plain' => ['txt', 'csv', 'text', 'conf', 'def', 'list', 'log', 'in', 'ini'],
        'text/prs.lines.tag' => 'dsc',
        'text/richtext' => 'rtx',
        'text/rtf' => 'rtf',
        'text/sgml' => ['sgml', 'sgm'],
        'text/slim' => ['slim', 'slm'],
        'text/stylus' => ['stylus', 'styl'],
        'text/tab-separated-values' => 'tsv',
        'text/troff' => ['t', 'tr', 'roff', 'man', 'me', 'ms'],
        'text/turtle' => 'ttl',
        'text/uri-list' => ['uri', 'uris', 'urls'],
        'text/vcard' => 'vcard',
        'text/vnd.curl' => 'curl',
        'text/vnd.curl.dcurl' => 'dcurl',
        'text/vnd.curl.mcurl' => 'mcurl',
        'text/vnd.curl.scurl' => 'scurl',
        'text/vnd.dvb.subtitle' => 'sub',
        'text/vnd.fly' => 'fly',
        'text/vnd.fmi.flexstor' => 'flx',
        'text/vnd.graphviz' => 'gv',
        'text/vnd.in3d.3dml' => '3dml',
        'text/vnd.in3d.spot' => 'spot',
        'text/vnd.sun.j2me.app-descriptor' => 'jad',
        'text/vnd.wap.wml' => 'wml',
        'text/vnd.wap.wmlscript' => 'wmls',
        'text/vtt' => 'vtt',
        'text/x-asm' => ['s', 'asm'],
        'text/x-c' => ['c', 'cc', 'cxx', 'cpp', 'h', 'hh', 'dic'],
        'text/x-component' => 'htc',
        'text/x-fortran' => ['f', 'for', 'f77', 'f90'],
        'text/x-handlebars-template' => 'hbs',
        'text/x-java-source' => 'java',
        'text/x-lua' => 'lua',
        'text/x-markdown' => ['markdown', 'md', 'mkd'],
        'text/x-nfo' => 'nfo',
        'text/x-opml' => 'opml',
        'text/x-pascal' => 'p', 'pas',
        'text/x-processing' => 'pde',
        'text/x-sass' => 'sass',
        'text/x-scss' => 'scss',
        'text/x-setext' => 'etx',
        'text/x-sfv' => 'sfv',
        'text/x-suse-ymp' => 'ymp',
        'text/x-uuencode' => 'uu',
        'text/x-vcalendar' => 'vcs',
        'text/x-vcard' => 'vcf',
        'text/xml' => 'xml',
        'text/yaml' => ['yaml', 'yml'],
        'video/3gpp' => ['3gp', '3gpp'],
        'video/3gpp2' => '3g2',
        'video/h261' => 'h261',
        'video/h263' => 'h263',
        'video/h264' => 'h264',
        'video/jpeg' => 'jpgv',
        'video/jpm' => ['jpm', 'jpgm'],
        'video/mj2' => ['mj2', 'mjp2'],
        'video/mp2t' => 'ts',
        'video/mp4' => ['mp4', 'mp4v', 'mpg4'],
        'video/mpeg' => ['mpeg', 'mpg', 'mpe', 'm1v', 'm2v'],
        'video/ogg' => 'ogv',
        'video/quicktime' => ['qt', 'mov'],
        'video/vnd.dece.hd' => ['uvh', 'uvvh'],
        'video/vnd.dece.mobile' => ['uvm', 'uvvm'],
        'video/vnd.dece.pd' => ['uvp', 'uvvp'],
        'video/vnd.dece.sd' => ['uvs', 'uvvs'],
        'video/vnd.dece.video' => ['uvv', 'uvvv'],
        'video/vnd.dvb.file' => 'dvb',
        'video/vnd.fvt' => 'fvt',
        'video/vnd.mpegurl' => ['mxu', 'm4u'],
        'video/vnd.ms-playready.media.pyv' => 'pyv',
        'video/vnd.uvvu.mp4' => ['uvu', 'uvvu'],
        'video/vnd.vivo' => 'viv',
        'video/webm' => 'webm',
        'video/x-f4v' => 'f4v',
        'video/x-fli' => 'fli',
        'video/x-flv' => 'flv',
        'video/x-m4v' => 'm4v',
        'video/x-matroska' => ['mkv', 'mk3d', 'mks'],
        'video/x-mng' => 'mng',
        'video/x-ms-asf' => ['asf', 'asx'],
        'video/x-ms-vob' => 'vob',
        'video/x-ms-wm' => 'wm',
        'video/x-ms-wmv' => 'wmv',
        'video/x-ms-wmx' => 'wmx',
        'video/x-ms-wvx' => 'wvx',
        'video/x-msvideo' => 'avi',
        'video/x-sgi-movie' => 'movie',
        'video/x-smv' => 'smv',
        'x-conference/x-cooltalk' => 'ice',
    ];

    /* ------ Constant substitution to configure uploader dynamically ------ */

    /**
     * true|false store into DATABASE or FILESYSTEM
     * default: true - may overwritten by global constants
     *
     * @var bool
     */
    private $UPLOAD_TARGET_DATABASE;

    /**
     * if DATABASE storage enabled , use filesystem as cache
     * default: false - may overwritten by global constants
     *
     * @var bool
     */
    private $UPLOAD_USE_DISK_CACHE;

    /**
     * if there are multiple files on Upload and an error occures: FALSE -> upload files with no errors, TRUE upload no file
     * default: true - may overwritten by global constants
     *
     * @var bool
     */
    private $UPLOAD_MULTIFILE_BREAOK_ON_ERROR;

    /**
     * how many files can be uploaded at once
     * default: 1 - may overwritten by global constants
     *
     * @var int
     */
    private $UPLOAD_MAX_MULTIPLE_FILES;

    //	/**
    //	 * need to be set globally
    //	 * path to DATABASE filecache or FILESYSTEM storage - no '/' at the ends7
    //	 * default: '' - may overwritten by global constants
    //	 * @var string
    //	 */
    //	static context
    //	UPLOAD_DISK_PATH;

    /**
     * in bytes - also check DB BLOB max size and php upload size limit in php.ini
     * default: 41943215 - may overwritten by global constants
     *
     * @var int
     */
    private $UPLOAD_MAX_SIZE;

    /**
     * upload blacklist
     * comma (,) seperated list,
     * regex possible
     * default: 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden' - may overwritten by global constants
     *
     * @var string
     */
    private $UPLOAD_PROHIBITED_EXTENSIONS;

    //	/**
    //	 * need to be set globally
    // 	 * 0 - dont use it, 1 - auto detect on apache modules, 2 force usage - if detection fails
    // 	 * default : 1 - may overwritten by global constants
    // 	 * @var integer
    // 	 */
    //	UPLOAD_MOD_XSENDFILE;

    /**
     * upload whitelist
     * if defined only files with this extensions may be uploaded,
     * comma (,) seperated list,
     * regex possible
     * default: .* - may overwritten by global constants
     *
     * @var string
     */
    private $UPLOAD_WHITELIST;

    /**
     * overwrite global upload config
     * exception: upload_path has to be set global if filestorage or disk cache is enabled
     * 	possible keys:
     * 		UPLOAD_TARGET_DATABASE
     * 		UPLOAD_USE_DISK_CACHE
     * 		UPLOAD_MULTIFILE_BREAOK_ON_ERROR
     * 		UPLOAD_MAX_MULTIPLE_FILES
     * 		UPLOAD_MAX_SIZE
     * 		UPLOAD_PROHIBITED_EXTENSIONS
     * 		UPLOAD_WHITELIST
     *
     * @param  array  $settings
     */
    public function initSettings($settings)
    {
        $this->UPLOAD_TARGET_DATABASE =
            ($settings['UPLOAD_TARGET_DATABASE'] ?? (! defined('UPLOAD_TARGET_DATABASE') || UPLOAD_TARGET_DATABASE));
        $this->UPLOAD_USE_DISK_CACHE =
            ($settings['UPLOAD_USE_DISK_CACHE'] ?? (defined('UPLOAD_USE_DISK_CACHE') && UPLOAD_USE_DISK_CACHE));
        $this->UPLOAD_MULTIFILE_BREAOK_ON_ERROR =
            ($settings['UPLOAD_MULTIFILE_BREAOK_ON_ERROR'] ?? (! defined('UPLOAD_MULTIFILE_BREAOK_ON_ERROR') || UPLOAD_MULTIFILE_BREAOK_ON_ERROR));
        $this->UPLOAD_MAX_MULTIPLE_FILES =
            ($settings['UPLOAD_MAX_MULTIPLE_FILES'] ?? (defined('UPLOAD_MAX_MULTIPLE_FILES') ?
                    UPLOAD_MAX_MULTIPLE_FILES
                    : 1));
        $this->UPLOAD_MAX_SIZE =
            ($settings['UPLOAD_MAX_SIZE'] ?? (defined('UPLOAD_MAX_SIZE') ?
                    UPLOAD_MAX_SIZE
                    : 41943215));
        $this->UPLOAD_PROHIBITED_EXTENSIONS =
            ($settings['UPLOAD_PROHIBITED_EXTENSIONS'] ?? (defined('UPLOAD_PROHIBITED_EXTENSIONS') ?
                    UPLOAD_PROHIBITED_EXTENSIONS
                    : 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden'));
        if (! defined('UPLOAD_MOD_XSENDFILE')) {
            define('UPLOAD_MOD_XSENDFILE', 1);
        }
        $this->UPLOAD_WHITELIST =
            ($settings['UPLOAD_WHITELIST'] ?? (defined('UPLOAD_WHITELIST') ?
                    UPLOAD_WHITELIST
                    : '.*'));
    }

    /* -------------------------------- */

    /**
     * constructor
     *
     * @param  DBConnector  $dbconnector
     * @param  array  $settings  set local file handler settings
     */
    public function __construct($dbconnector, $settings = null)
    {
        $this->db = new DbFilePDO($dbconnector);
        $this->initSettings($settings);
    }

    /**
     * delete all files by link id
     *
     * @param  int  $link
     */
    public function deleteFilesByLinkId($link)
    {
        $files = $this->db->getFilesByLinkId($link);
        // delete from db
        $this->db->deleteFiledataByLinkId($link);
        $this->db->deleteFileinfoByLinkId($link);
        // delete from harddisk
        if (is_array($files)) {
            foreach ($files as $file) {
                $path = self::getDiskpathOfFile($file);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        }
    }

    /**
     * clean up upload folder
     * delete all directories which are not in db
     */
    public function cleanupDirectories()
    {
        // get all directory names
        $base = self::getBaseDirPath();
        $files = array_diff(scandir($base), ['.', '..']);
        // get all link ids
        $links = $this->db->getAllFileLinkIds();
        $dirs = [];
        // delete all directories not in links
        foreach ($files as $file) {
            if (is_dir("$base/$file") && ! in_array($file, $links, true)) {
                self::delTree($base / $file);
            }
        }
    }

    /**
     * returns fileinfo by id
     *
     * @return array <File>
     */
    public function filelist($linkId): array
    {
        return $this->db->getFilesByLinkId($linkId);
    }

    /**
     * deliver file to user
     *   raw flag for display/download
     *   key hash value
     *
     * @param  bool  $noinline  disposition: false -> 'inline'|true -> 'attachment'
     */
    public function deliverFileData(File $file, $noinline = false): void
    {

        if (! $this->UPLOAD_TARGET_DATABASE) { // disk FILESYSTEM storage ------------
            // if  saved in Laravel
            if (! Storage::exists(self::getDiskpathOfFile($file))) {
                error_log("FILE Error: File not found on disk. File Id: {$file->id} File Path: ".self::getDiskpathOfFile($file));
            } else {
                header('Last-Modified: '.$file->getAddedOnDate()->format('D, d M Y H:i:s').' GMT', true);
                if ($file->size) {
                    header('Content-Length: '.$file->size);
                }
                if ($file->mime) {
                    header("Content-Type: {$file->mime}");
                } else {
                    header('Content-Type: application/octet-stream');
                }
                // apache deliver
                if (self::hasModXSendfile()) {
                    header('X-Sendfile: '.self::getDiskpathOfFile($file));

                    return;
                }

                header('Content-Disposition: '.(! $noinline ? 'inline' : 'attachment').'; filename="'.$file->filename.(($file->fileextension) ? '.'.$file->fileextension : '').'"');
                echo Storage::get(self::getDiskpathOfFile($file));

                return;
            }
        } else { // DATABASE storage ----------------
            $data = $this->db->getFiledataBinary($file->data);
            header('Last-Modified: '.$file->getAddedOnDate()->format('D, d M Y H:i:s').' GMT', true);
            if ($file->size) {
                header('Content-Length: '.$file->size);
            }
            if ($file->mime) {
                header("Content-Type: {$file->mime}");
            } else {
                header('Content-Type: application/octet-stream');
            }
            if ($this->UPLOAD_USE_DISK_CACHE) {
                self::checkCreateDirectory(self::getDirpathOfFile($file));
                file_put_contents(self::getDiskpathOfFile($file), $data);
                // apache deliver
                if (self::hasModXSendfile()) {
                    // apache deliver
                    header('X-Sendfile: '.self::getDiskpathOfFile($file));

                    return;
                }
            }
            header('Content-Disposition: '.(! $noinline ? 'inline' : 'attachment').'; filename="'.$file->filename.(($file->fileextension) ? '.'.$file->fileextension : '').'"');
            echo $data;

            return;
        }
    }

    /**
     * return binary file data
     * Remember: trigger close DB file if DB storage is enabled
     *   function: close_db_file()
     *
     * @param  bool  $cache
     * @return false|string
     */
    public function getFiledataBinary(File $file, $cache = true)
    {
        if (! $this->UPLOAD_TARGET_DATABASE) { // disk FILESYSTEM storage ------------
            if (! file_exists(self::getDiskpathOfFile($file))) {
                error_log("FILE Error: File not found on disk. File Id: {$file->id} File Path: ".self::getDiskpathOfFile($file));
            } else {
                return file_get_contents(self::getDiskpathOfFile($file));
            }
        } else { // DATABASE storage ----------------
            if ($this->UPLOAD_USE_DISK_CACHE && file_exists(self::getDiskpathOfFile($file))) {
                return file_get_contents(self::getDiskpathOfFile($file));
            } else {
                $data = $this->db->getFiledataBinary($file->data);
                if ($cache) {
                    self::checkCreateDirectory(self::getDirpathOfFile($file));
                    file_put_contents(self::getDiskpathOfFile($file), $data);
                }

                return $data;
            }
        }
    }

    /**
     * return diskdirectory of given file
     */
    public static function getBaseDirPath(): string
    {
        return 'auslagen';
    }

    /**
     * return diskdirectory of given file
     */
    public static function getDirpathOfFile(File $file): string
    {
        return self::getBaseDirPath().'/'.$file->link;
    }

    /**
     * return filepath to given file
     */
    public static function getDiskpathOfFile(File $file): string
    {

        return self::getDirpathOfFile($file).'/'.$file->hashname.'.pdf';
    }

    /**
     * file db hash
     * check if file exists
     * run this and may check filepermissions after that
     */
    public function checkFileHash(string $hash): ?File
    {
        // check hash only contains hey letters, length 64
        $re = '/^([0-9a-f]{64}|[0-9a-zA-Z]{40})$/m';
        if (! preg_match($re, $hash)) {
            return null;
        }

        return $this->db->getFileInfoByHash($hash);
    }

    /**
     * ACTION upload / store file
     * Notice: to do permission control, check permissions on seperat controller and call this controller if all went well
     *
     * @param  int  $link  link id
     * @param  string  $base_key  $_FILES key default: file
     * @return array keys: success:bool, errors:array of string, filecounter:int, fileinfo:array
     *
     * @throws \Exception
     */
    public function upload(int $link, string $base_key, ?int $auslagen_id): array
    {
        $result = [
            'success' => true,
            'error' => [],
            'filecounter' => 0,
            'fileinfo' => [],
        ];

        if (! isset($_FILES)
            || count($_FILES) === 0
            || ! isset($_FILES[$base_key])
            || ! isset($_FILES[$base_key]['error'])
            || ! isset($_FILES[$base_key]['name'])
            || count($_FILES[$base_key]['name']) === 0) {
            return $result;
        }

        // handle fileupload === CHECK FILES ===================================
        if (! isset($_POST['nofile']) && count($_FILES) > 0 &&
            isset($_FILES[$base_key]) &&
            isset($_FILES[$base_key]['error']) &&
            isset($_FILES[$base_key]['name']) &&
            is_array($_FILES[$base_key]['error']) &&
            count($_FILES[$base_key]['name']) > 0) {
            $files = [];
            $forbidden_file_types = preg_replace('/\s*[,;\|#]\s*/', '|', $this->UPLOAD_PROHIBITED_EXTENSIONS);
            $file_whitelist = preg_replace('/\s*[,;\|#]\s*/', '|', isset($this->UPLOAD_WHITELIST) ? $this->UPLOAD_WHITELIST : '.*');

            if (count($files) > $this->UPLOAD_MAX_MULTIPLE_FILES) {
                $result['error'][] = 'Too many simultaneous Files on Upload.';
            } else {
                // check files
                foreach ($_FILES[$base_key]['name'] as $id => $filename) {
                    // ERROR HANDLING ===========================================================
                    // file error ------------------------------------------------
                    if ($_FILES[$base_key]['error'][$id] != UPLOAD_ERR_OK) {
                        switch ($_FILES[$base_key]['error'][$id]) {
                            case UPLOAD_ERR_INI_SIZE:
                                $result['error'][] = 'Die hochgeladene Datei überschreitet die in der Anweisung upload_max_filesize in php.ini festgelegte Größe.';
                                break;
                            case UPLOAD_ERR_FORM_SIZE:
                                $result['error'][] = 'Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigröße';
                                break;
                            case UPLOAD_ERR_PARTIAL:
                                $result['error'][] = 'Die Datei wurde nur teilweise hochgeladen.';
                                break;
                            case UPLOAD_ERR_NO_FILE:
                                $result['error'][] = 'Es wurde keine Datei hochgeladen. ';
                                break;
                            case UPLOAD_ERR_NO_TMP_DIR:
                                $result['error'][] = 'Fehlender temporärer Ordner.';
                                break;
                            case UPLOAD_ERR_CANT_WRITE:
                                $result['error'][] = 'Speichern der Datei auf die Festplatte ist fehlgeschlagen.';
                                break;
                            case UPLOAD_ERR_EXTENSION:
                                $result['error'][] = 'Eine PHP Erweiterung hat den Upload der Datei gestoppt. ';
                                break;
                            default:
                                $result['error'][] = 'Undefinierter upload Fehler. ID: '.$_FILES[$base_key]['error'][$id];
                                break;
                        }

                        continue;
                    }
                    // is no uploaded file ---------------------------------------
                    if (! is_uploaded_file($_FILES[$base_key]['tmp_name'][$id])) {
                        error_log('SECURITY ALERT: Try to save nonuploaded file.');
                        $result['error'][] = 'Es wird versucht eine nicht hochgeladene Datei zu speichern.';

                        continue;
                    }
                    // file size -------------------------------------------------
                    if ($_FILES[$base_key]['size'][$id] > $this->UPLOAD_MAX_SIZE) {
                        $result['error'][] = 'Datei ist zu groß';

                        continue;
                    }

                    $file = new File;
                    $vali = new Validator;
                    $pathinfo = pathinfo($_FILES[$base_key]['name'][$id]);

                    // added on, set in database, or dbModel
                    $file->added_on = null;

                    // filename
                    $tmp_name = $_FILES[$base_key]['tmp_name'][$id];
                    $vali->V_filename($pathinfo['filename']);
                    $pathinfo['filename'] = $vali->getIsError() ? $tmp_name : $vali->getFiltered();
                    $file->filename = $pathinfo['filename'];
                    $file->filename = str_replace('..', '.', $file->filename);
                    $file->filename = str_replace('..', '.', $file->filename);
                    if ($file->filename == '') {
                        $result['error'][] = 'empty or invalid filename';

                        continue;
                    }

                    // hashname (replaced by laravel later)
                    // $file->hashname = strtolower(generateRandomString(32));

                    // link
                    if (is_int($link)) {
                        $file->link = $link;
                    } elseif (is_array($link) && isset($link[$tmp_name]) && is_int($link[$tmp_name])) {
                        $file->link = $link[$tmp_name];
                    } else {
                        $result['error'][] = "No link id found for '$tmp_name'";

                        continue;
                    }

                    // size
                    $file->size = $_FILES[$base_key]['size'][$id];

                    // mime + fileextension
                    $ext1 = $pathinfo['extension'] ?? '';
                    $ext1 = mb_strtolower(substr($ext1, 0, 44));
                    if (preg_match('/'.$forbidden_file_types.'$/i', $ext1)) {
                        $result['error'][] = 'You are not allowed to upload files with this extension';

                        continue;
                    }
                    if (! preg_match('/'.$file_whitelist.'$/i', $ext1)) {
                        $result['error'][] = 'You are not allowed to upload files with this extension';

                        continue;
                    }
                    if ($ext1 != '') {
                        $vali->V_filename($ext1);
                        $ext1 = $vali->getIsError() ? '' : $vali->getFiltered();
                    }

                    $file->fileextension = $ext1;

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES[$base_key]['tmp_name'][$id]);
                    if ($mime) {
                        $file->mime = $mime;
                        $ext2 = self::extensionFromMime($mime);
                        if ($ext2) {
                            if (! is_array($ext2)) {
                                $ext2 = [$ext2];
                            }
                            $continue = false;
                            foreach ($ext2 as $ex) {
                                if (preg_match('/'.$forbidden_file_types.'$/i', $ex)) {
                                    $result['error'][] = 'You are not allowed to upload files with this extension (different mime detected)';
                                    $continue = true;
                                    break;
                                }
                                if (! preg_match('/'.$file_whitelist.'$/i', $ex)) {
                                    $result['error'][] = 'You are not allowed to upload files with this extension (different mime detected)';
                                    $continue = true;
                                    break;
                                }
                            }
                            if ($continue) {
                                continue;
                            }
                        }
                        if ($ext2 && $ext1 != '' && ! in_array($ext1, $ext2, true)) {
                            $err = 'File extension does not match to your mime type.';
                            $result['error'][] = $err;
                            error_log($err." --- Org Ext: '$ext1'; Mime: '$file->mime'; MimeExt: '$ext2[0]'");

                            continue;
                        }
                    }

                    // encoding
                    if ($file->mime && mb_strpos($file->mime, 'text/') === 0) {
                        $finfo2 = new finfo(FILEINFO_MIME_ENCODING);
                        $enc = $finfo->file($_FILES[$base_key]['tmp_name'][$id]);
                        if ($enc) {
                            $file->encoding = $enc;
                        }
                    }

                    // add file to upload list
                    $files[$id] = $file;
                }
            }
            if ($this->UPLOAD_MULTIFILE_BREAOK_ON_ERROR && count($result['error']) > 0) {
                $result['error'][] = 'Upload aborted due to an error.';
            } else {
                // check db for existing files
                /** @var File $file */
                foreach ($files as $id => $file) {
                    if ($this->db->checkFileExists($file->link, $file->filename, $file->fileextension)) {
                        $result['error'][] = "The File '$file->filename' already exists on this path.";
                        unset($files[$id]);
                    }
                }
                if (($this->UPLOAD_MULTIFILE_BREAOK_ON_ERROR && count($result['error']) > 0) || $this->db->isError()) {
                    $result['error'][] = 'Upload aborted due to an error.';
                    $result['success'] = false;
                } else {
                    $result['fileinfo'] = $files;
                }
            }
        }

        // UPLOAD FILES ===============================================
        if (count($result['fileinfo']) == 0) {
            return $result;
        } else {
            // FILESYSTEM storage ---------------
            if (! $this->UPLOAD_TARGET_DATABASE) {
                foreach ($result['fileinfo'] as $id => $file) {
                    // $dir = self::getDirpathOfFile($file);
                    // create directory if not extists
                    // self::checkCreateDirectory($dir);
                    // upload file
                    // $uploadfile = self::getDiskpathOfFile($file);

                    // UPLOAD FILES Laravel Style
                    request()->validate([
                        $base_key => 'required',
                        $base_key.'.'.$id => 'required|file|mimes:pdf',
                    ]);
                    $lara_file = request()->file($base_key)[$id];
                    $lara_path = $lara_file->store('auslagen/'.$auslagen_id);
                    // get everything between last / and .pdf file extension
                    $file->hashname = substr($lara_path, strrpos($lara_path, '/') + 1, -4);
                    // create file entry
                    $file->id = $this->db->createFile($file);
                    $dberror = $this->db->isError();
                    // create data entry
                    if (! $dberror) {
                        $fdid = $this->db->createFileDataPath($lara_path);
                        $dberror = $this->db->isError();
                        $file->data = $fdid;
                    }
                    // update link data
                    if (! $dberror) {
                        $dberror = ! $this->db->updateFile_DataId($file);
                    }
                    // check for error
                    if ($dberror) {
                        Storage::delete($lara_path);
                        unset($result['fileinfo'][$id]);
                        $result['error'][] = 'DB Error -> remove file';
                        if ($this->UPLOAD_MULTIFILE_BREAOK_ON_ERROR) {
                            $result['success'] = false;
                            break;
                        }
                    }
                }
            } else { // DATABASE storage -------------
                throw new LegacyDieException('Deprecated Database storage');
            }

            return $result;
        }
    }

    /**
     * test if server supports xsendfile headers (mod_xsendfile)
     */
    public static function hasModXSendfile(): bool
    {
        if (! UPLOAD_MOD_XSENDFILE) {
            return false;
        } elseif (UPLOAD_MOD_XSENDFILE == 2) {
            return true;
        }
        if (function_exists('apache_get_modules')) {
            $modlist = apache_get_modules();
            if (in_array('mod_xsendfile', $modlist, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * convert arrays (and other values) to text
     *
     * @return false|string
     */
    public static function array2text(array $a)
    {
        return json_encode($a);
    }

    /**
     * convert text to array
     */
    public static function text2array(string $t)
    {
        return json_decode($t, true);
    }

    /**
     * convert text to binary data
     */
    public static function text2binary(string $t)
    {
        return gzcompress($t);
    }

    /**
     * convert binary data to text
     *
     * @param  binary  $b
     */
    public static function binary2text($b)
    {
        return gzuncompress($b);
    }

    /**
     * convert array to binary data
     *
     * @param  array  $a
     */
    public static function array2binary($a)
    {
        return self::text2binary(self::array2text($a));
    }

    /**
     * convert binary data to array
     *
     * @param  binary  $b
     */
    public static function binary2array($b)
    {
        return self::text2array(self::binary2text($b));
    }

    /**
     * recursively delete directories and containing files
     * may echo error messages
     *
     * @param  string  $dir  directory path
     * @return bool success
     */
    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delTree("$dir/$file");
            } else {
                $res = unlink("$dir/$file");
                if (! $res) {
                    echo '<strong>ERROR on unlinking file</strong>: '."$dir/$file<br>";
                }
            }
        }
        $res = rmdir($dir);
        if (! $res) {
            echo '<strong>ERROR on removing directory</strong>: '."$dir<br>";
        }

        return $res;
    }

    /**
     * create directory if it does not exists
     * allows recursive directory creation
     *
     * @param  string  $dir  directory path
     * @return bool success
     */
    public static function checkCreateDirectory(string $dir): bool
    {
        return ! (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir));
    }

    /**
     * prettify filesize
     * calculate short filesize from byte file size
     *
     * @param  numeric  $filesize  in bytes
     * @return string NaN| prettyfied filesize
     */
    public static function formatFilesize($filesize): string
    {
        $unit = ['Byte', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $standard = 1024;
        if (is_numeric($filesize)) {
            $count = 0;
            while (($filesize / $standard) >= 0.9) {
                $filesize /= $standard;
                $count++;
            }

            return round($filesize, 2).' '.$unit[$count];
        }

        return 'NaN';
    }

    /**
     * return fileextension from mime type
     *
     * @return false|mixed
     */
    public static function extensionFromMime(string $mime)
    {
        return self::$mimeMap[$mime] ?? false;
    }
}
