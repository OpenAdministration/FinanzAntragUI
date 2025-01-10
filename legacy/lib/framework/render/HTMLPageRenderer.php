<?php

namespace framework\render;

use App\Exceptions\LegacyRedirectException;
use framework\render\html\BT;
use framework\render\html\Html;
use framework\render\html\HtmlAlert;

class HTMLPageRenderer
{
    private static $profiling_timing;

    private static $profiling_names;

    private static $profiling_sources;

    private static $errorHandler;

    private static $tabsConfig;

    private $bodyContent;

    private $titel;

    protected $routeInfo;

    public function __construct($routeInfo, $bodyContent = '')
    {
        $this->routeInfo = $routeInfo;
        if (isset($this->routeInfo['titel'])) {
            $this->titel = $this->routeInfo['titel'];
        } else {
            $this->titel = 'StuRa Finanzen';
        }
        $this->bodyContent = $bodyContent;
    }

    /**
     * @param  $name  string Name des Profiling Flags
     */
    public static function registerProfilingBreakpoint(string $name): void
    {
        self::$profiling_timing[] = microtime(true);
        $bt = debug_backtrace();
        self::$profiling_sources[] = array_shift($bt);
        self::$profiling_names[] = $name;
    }

    /**
     * @param  $tabs  array keys are tabnames, values are htmlcode inside tab header
     * @param  $linkbase  string linkbase - where clicked link will go - followed by tabnames
     * @param  string|int  $activeTab  string
     */
    public static function setTabs(array $tabs, string $linkbase, string|int $activeTab): void
    {
        self::$tabsConfig = ['tabs' => $tabs, 'linkbase' => $linkbase, 'active' => $activeTab];
    }

    public static function showErrorAndDie(ErrorHandler $errorHandler): void
    {
        self::$errorHandler = $errorHandler;
        if (request()->method() === 'POST') {
            self::$errorHandler->renderJson();
            exit();
        }
        (new self([]))->render();
        exit();
    }

    private function hasError(): bool
    {
        return isset(self::$errorHandler);
    }

    private function renderError(): void
    {
        self::$errorHandler->render();
    }

    public function render(): void
    {
        $this->renderHtmlHeader();
        // $this->renderNavbar();
        // $this->renderSiteNavigation();
        echo "<div class='container col-xs-12 col-lg-offset-2 col-lg-8'>";
        if ($this->hasError()) {
            $this->renderError();
        } else {
            $this->renderFlash();
            if (! empty(self::$tabsConfig)) {
                $this->renderPanelTabs(self::$tabsConfig['tabs'], self::$tabsConfig['linkbase'], self::$tabsConfig['active']);
            }
            echo $this->bodyContent;
            if (! empty(self::$tabsConfig)) {
                echo '</div></div>';
            }
        }
        echo '</div>';
        if (! $this->hasError()) {
            $this->renderModals();
        }
        $this->renderFooter();
    }

    private function renderFlash(): void
    {
        foreach (request()?->session()->get('flash', []) as $alertHtml) {
            echo $alertHtml->__toString();
        }
        request()?->session()->forget('flash');
    }

    private function renderHtmlHeader(): void
    {
        $this->setPhpHeader(); ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <title><?php echo $this->titel; ?></title>
            <?php echo $this->includeCSS(); ?>
            <?php echo $this->includeJS(); ?>
            <base target="_parent">
            <meta charset="utf-8">
        </head>
        <body>
        <?php
    }

    private function setPhpHeader(): void
    {
        // by micha-dev
        // https://people.mozilla.com/~bsterne/content-security-policy/details.html
        // https://wiki.mozilla.org/Security/CSP/Specification
        // header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
        // header('X-Frame-Options: DENY');
        // header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        // header('Cache-Control: post-check=0, pre-check=0', false);
        // header('Pragma: no-cache');
    }

    private function includeCSS(): string
    {
        $out = '';
        $defaultCssFiles = ['bootstrap.min', 'font-awesome.min'];
        $cssFiles = $defaultCssFiles;
        if (isset($this->routeInfo['load'])) {
            $cssFiles = array_merge($cssFiles, ...array_column($this->routeInfo['load'], 'css'));
        }
        foreach ($cssFiles as $cssFile) {
            $out .= "<link rel='stylesheet' href='".asset("css/$cssFile.css")."'>".PHP_EOL;
        }
        $out .= "<link rel='stylesheet' href='".asset('css/main.css')."'>".PHP_EOL;

        return $out;
    }

    private function includeJS(): string
    {
        $out = '';
        $defaultJsFiles = [
            'jquery-3.6.3.min',
            'bootstrap.min',
            'validator',
            'numeral.min',
            'numeral-locales.min',
        ];
        /*
        jquery-3.1.1.min.js
        bootstrap.min.js
        bootstrap-select.min.js
        validator.js
        bootstrap-datepicker.min.js
        bootstrap-datepicker.de.min.js
        bootstrap-treeview.js
        fileinput.min.js
        fileinput.de.js
        fileinput-themes/gly/theme.js
        iban.js
        numeral.min.js
        numeral-locales.min.js
        main.js
        */
        $jsFiles = $defaultJsFiles;
        if (isset($this->routeInfo['load'])) {
            $jsFiles = array_merge($jsFiles, ...array_column($this->routeInfo['load'], 'js'));
        }
        // var_dump($this->routeInfo["load"]);
        foreach ($jsFiles as $jsFile) {
            $out .= "<script src='".asset("js/$jsFile.js")."'></script>".PHP_EOL;
        }
        $out .= "<script src='".asset('js/main.js')."'></script>".PHP_EOL;

        return $out;
    }

    private function renderPanelTabs($tabs, $linkbase, $activeTab): void
    {

        ?>
        <div class="panel panel-default">
            <div class="panel-heading panel-heading-with-tabs">
                <ul class="nav nav-tabs">
                    <?php
                    foreach ($tabs as $link => $fullname) {
                        echo "<li class='".(($link === $activeTab) ? 'active' : '')."'><a href='$linkbase$link'>$fullname</a></li>";
                    } ?>
                </ul>
            </div>
            <div class="panel-body">
        <?php
    }

    private function renderModals(): void
    {
        $this->buildModal('please-wait', 'Bitte warten - Die Anfrage wird verarbeitet.', ''.
            '<div class="planespinner"><div class="rotating-plane"></div></div>');
        $this->buildModal('server-message', 'Antwort vom Server', '...');
        $this->buildModal('server-question', 'Antwort vom Server', '...', 'Ok', 'Fenster schließen');
        $this->buildModal(
            'rename-file',
            'Datei umbenennen',
            "<div class='form-group'>
                            <label for='rename-file-oldname'>Ursprünglicher Name</label>
                            <input type='text' class='form-control' id='rename-file-oldname' readonly='readonly'>
                        </div>
                        <div class='form-group'>
                            <label for='rename-file-newname'>Neuer Name</label>
                            <input type='text' class='form-control' id='rename-file-newname'>
                        </div>"
        );
        $this->buildModal(
            'delete-file',
            'Datei löschen',
            "<div class='form-group'>
                            <label for='delete-file-name'>Name</label>
                            <input type='text' class='form-control' id='delete-file-name' readonly='readonly'>
                        </div>
                        <div class='form-group'>
                            <label for='delete-file-size'>Größe</label>
                            <input type='text' class='form-control' id='delete-file-size' readonly='readonly'>
                        </div>",
            'Abbruch',
            'Datei löschen',
            true
        );
        $this->buildModal(
            'please-reload',
            'Bitte Formular speichern und neu zum Bearbeiten öffnen',
            'Dieses Formularelement verändert die Bearbeitbarkeit von Formularfeldern. Das Formular sollte daher nun gespeichert und neu zum Bearbeiten geöffnet werden.',
            'Abbruch',
            'Formular speichern und neu zum Bearbeiten öffnen'
        );
        $this->buildModal(
            'server-error',
            "<div class='default-head'>Es ist ein unerwarteter Fehler aufgetreten.</div><div class='js-head'></div>",
            "<div class='default-content'>Die Seite wird gleich automatisch neu geladen.<div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'danger'
        );
        $this->buildModal(
            'server-success',
            "<div class='default-head'>Request erfolgreich.</div><div class='js-head'></div>",
            "<div class='default-content'><div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'success'
        );
        $this->buildModal(
            'server-warning',
            "<div class='default-head'>Warnung</div><div class='js-head'></div>",
            "<div class='default-content'><div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'warning'
        );
        $this->buildModal(
            'server-file',
            "<div class='js-head'></div>",
            "<div class='js-content'></div>",
            null,
            null,
            'success'
        );
    }

    public static function injectModal($id, $titel, $bodycontent, $abortLabel = null, $actionLabel = null, $danger = false, ?callable $canConfirm = null, string $actionButtonType = 'button')
    {
        (new HTMLPageRenderer([]))->buildModal($id, $titel, $bodycontent, $abortLabel, $actionLabel, $danger, $canConfirm, $actionButtonType);
    }

    private function buildModal($id, $titel, $bodycontent, $abortLabel = null, $actionLabel = null, $danger = false,
        ?callable $canConfirm = null, string $actionButtonType = 'button'): void
    {
        if ($danger === 'danger') {
            $buttonType1 = 'primary';
            $buttonType2 = 'danger';
        } else {
            $buttonType1 = 'default';
            $buttonType2 = 'primary';
        }

        $disabled = isset($canConfirm) && $canConfirm() === false;

        $hasFooter = isset($abortLabel) || isset($actionLabel); ?>
        <div class='modal fade' id='<?php echo $id; ?>-dlg' tabindex='-1' role='dialog'
             aria-labelledby='<?php echo $id; ?>-label'>
            <div class='modal-dialog' <?php echo ($danger === 'danger') ? 'style="min-width: 75%;"' : ''; ?> role='document'>
                <div class='modal-content'>
                    <div class='modal-header<?php echo ($danger) ? " btn-$danger' style='border-top-left-radius: 5px; border-top-right-radius: 5px;" : ''; ?>'>
                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span
                                    aria-hidden='true'>&times;</span>
                        </button>
                        <h4 class='modal-title' id='<?php echo $id; ?>-label'><?php echo $titel; ?></h4>
                    </div>
                    <div class='modal-body' id='<?php echo $id; ?>-content'>
                        <?php echo $bodycontent; ?>
                    </div>
                    <?php if ($hasFooter) { ?>
                        <div class='modal-footer'>
                            <?php if (isset($abortLabel)) { ?>
                                <button type='button' class='btn btn-<?php echo $buttonType1; ?>'
                                        data-dismiss='modal'><?php echo $abortLabel; ?></button>
                            <?php } ?>
                            <?php if (isset($actionLabel)) { ?>
                                <button type="<?= $actionButtonType ?>" class='btn btn-<?php echo $buttonType2; ?>'
                                        id='<?php echo $id; ?>-btn-action' <?= $disabled ? 'disabled' : '' ?>
                                >
                                    <?php echo $actionLabel; ?>
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Print all Profiling Flags from HTMLPageRenderer::registerProfilingBreakpoint()
     */
    private function renderProfiling(): void
    {
        $sum = 0;
        $size = isset(self::$profiling_timing) ? count(self::$profiling_timing) : 0;
        if ($size === 0) {
            return;
        }
        $out = '';
        for ($i = 0; $i < $size - 1; $i++) {
            $out .= "<span class='profiling-names'><strong>".self::$profiling_names[$i].'</strong></span>';
            $out .= "<i class='profiling-source'>".
                basename(self::$profiling_sources[$i]['file']).':'.
                self::$profiling_sources[$i]['line'].'
                </i>';
            $sum += self::$profiling_timing[$i + 1] - self::$profiling_timing[$i];
            $out .= '<div>'.sprintf('&nbsp;&nbsp;&nbsp;%f<br>', self::$profiling_timing[$i + 1] - self::$profiling_timing[$i]).'</div>';
        }
        $out .= "<span class='profiling-names'><strong>".self::$profiling_names[$size - 1].'</strong></span>';
        $out .= "<i class='profiling-source'>".
            basename(self::$profiling_sources[$size - 1]['file']).':'.
            self::$profiling_sources[$size - 1]['line'].'
                </i>';
        // Wrapp all output till now with div
        $out = '<div class="profiling-output"><h3><i class="fa fa-fw fa-angle-toggle"></i> Ladezeit: '.sprintf('%f', $sum).'</h3>'.$out;
        $out .= '</div>';
        echo $out;
    }

    private function renderFooter(): void
    {
        ?>
        </body>
        </html>
        <?php
    }

    /**
     * @param  string  $TYPE  const of BT::
     */
    public static function addFlash(string $TYPE, string $string, mixed $debugInfo = ''): void
    {
        $alert = HtmlAlert::make($TYPE)->body($string);
        if (DEV) {
            if (! is_string($debugInfo)) {
                $debugInfo = var_export($debugInfo, true);
            }
            $strong = match ($TYPE) {
                BT::TYPE_SUCCESS => 'Erfolg',
                BT::TYPE_WARNING => 'Warnung',
                BT::TYPE_DANGER => 'Fehler',
                BT::TYPE_INFO => ' Info',
                // primary?
                // secondary?
            };
            $alert->appendBody(PHP_EOL.Html::tag('i')->body($debugInfo), false)->strongMsg($strong);
        }
        $flashs = request()?->session()->get('flash', []);
        $flashs[] = $alert;
        request()?->session()->put('flash', $flashs);
    }

    public function getBodyContent(): string
    {
        return $this->bodyContent;
    }

    public function appendRendererContent(Renderer $renderObject): void
    {
        ob_start();
        $renderObject->render();
        $content = ob_get_clean();
        $this->appendToBody($content);
    }

    public function appendToBody($htmlContent): void
    {
        $this->bodyContent .= $htmlContent;
    }

    public static function redirect($url): void
    {
        throw new LegacyRedirectException(redirect($url));
    }
}
