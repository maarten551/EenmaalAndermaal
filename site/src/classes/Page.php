<?php
namespace src\classes;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Messages\Warning;
use src\classes\Models\Item;
use src\classes\Models\Question;
use src\classes\Models\User;

abstract class Page {
    /**
     * @var DatabaseHelper
     */
    protected $databaseHelper;
    /**
     * @var UserHelper
     */
    protected $userHelper;
    /**
     * @var User
     */
    protected $loggedInUser;
    /**
     * @var HTMLBuilder
     */
    protected $HTMLBuilder;

    abstract protected function createHTML();

    /**
     * @param $templateFileName
     */
    protected function __construct($templateFileName) {
        $this->HTMLBuilder = new HTMLBuilder($templateFileName);
        $this->databaseHelper = new DatabaseHelper();
        $this->userHelper = new UserHelper($this->databaseHelper, $this->HTMLBuilder);

        if($this->loggedInUser === null) {
            $this->loggedInUser = $this->userHelper->getLoggedInUser();
        }

        $this->endExpiredAuctions($this->databaseHelper);
        $this->handleRequestParameters();
    }

    protected function __destruct() {
        $this->databaseHelper->closeConnection();
    }

    protected function handleRequestParameters() {
        if(array_key_exists('login', $_POST)) {
            $this->loggedInUser = $this->userHelper->loginUser($_POST['login-username'], $_POST['login-password']);
        } else if (array_key_exists('forgot-password-button', $_POST)) {
            $this->handleForgottenPassword();
        } else if (array_key_exists('logout', $_GET)) {
            $this->userHelper->logoutUser();
            $this->loggedInUser = null;
        } else if (array_key_exists('register', $_POST)) {
            $this->userHelper->registerUser();
        }
    }

    private function handleForgottenPassword() {
        if(array_key_exists('forgot-password-username', $_POST) && !empty($_POST['forgot-password-username'])) {
            if (array_key_exists('forgot-password-password', $_POST) && !empty($_POST['forgot-password-password']) && array_key_exists('forgot-password-password-repeat', $_POST) && $_POST['forgot-password-password-repeat'] === $_POST['forgot-password-password']) {
                if (array_key_exists('forgot-password-question', $_POST) && !empty($_POST['forgot-password-question'])) {
                    $success = false;
                    $question = Question::GET_BY_QUESTION_TEXT($this->databaseHelper, $_POST['forgot-password-question']);
                    $user = new User($this->databaseHelper, $_POST['forgot-password-username']);

                    if ($user !== null && $question !== null) {
                        if ($user->getQuestionId() === $question->getId()) {
                            if ($user->getQuestionAnswer() === $_POST['forgot-password-question-answer']) {
                                $success = true;
                                $user->setPassword($_POST['forgot-password-password']);
                                $this->userHelper->hashPassword($user);
                                $user->save();
                                $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Wachtwoord veranderen gelukt", "Uw wachtwoord is veranderd, gebruik het meegegeven wachtwoord om voortaan in te loggen."));
                            }
                        }
                    }

                    if ($success === false) {
                        $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Wachtwoord veranderen mislukt", "De meegegeven informatie komt niet overeen met de gekozen gebruiker."));
                    }
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Wachtwoord veranderen mislukt", "Er is geen vraag meegegeven."));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Wachtwoord veranderen mislukt", "Een wachtwoord veld is niet ingevuld of de wachtwoord velden komen niet overeen, de wachtwoord zal later gebruikt worden om weer in te loggen."));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Wachtwoord veranderen mislukt", "Er is geen gebruikersnaam meegegeven."));
        }
    }

    protected function generateLoginAndRegisterTemplates() {
        if($this->loggedInUser === null) {
            $registerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\register-modal.html");
            $loginModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\inloggen-modal.html");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loginModal);
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("register-modal", $registerModal);
            $this->generateQuestionTemplate();
        } else {
            $loggedOnTemplate = new HTMLParameter($this->HTMLBuilder, "content\\user-is-logged-on.html");
            $loggedOnTemplate->addTemplateParameterByString("user-username", $this->loggedInUser->getUsername());
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loggedOnTemplate);
        }
    }

    protected function generateQuestionTemplate(){
        $questions = $this->getQuestions();
        $questionTemplates = array();
        foreach($questions as $question){
            $questionTemplate = new HTMLParameter($this->HTMLBuilder, "content\\question.html");
            $questionTemplate->addTemplateParameterByString("question-name", $question->getQuestionText());
            $questionTemplate->addTemplateParameterByString("question-value", $question->getQuestionText());
            $questionTemplates[] = $questionTemplate;
        }

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("questions", $this->HTMLBuilder->joinHTMLParameters($questionTemplates));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("max-birthdate", date('d-m-Y'));
    }

    /**
     * @return Question[]
     */
    protected function getQuestions(){
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "select questionText from question");
        if($statement === false) {
            echo "Error in executing statement.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            $questions = array();
            while ($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $question = new Question($this->databaseHelper);
                $question->mergeQueryData($row);
                $questions[] = $question;
            }
            return $questions;
        }
    }

    protected function redirectToIndex() {
        $pageName = strtolower(substr(get_class($this), 0, 1)).substr(get_class($this), 1);
        $redirectLink = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "/$pageName.php"));

        header("location: $redirectLink/index.php");
        die();
    }

    private function endExpiredAuctions(DatabaseHelper $databaseHelper) {
        $getExpiredAuctionsQuery = "SELECT id FROM [item] WHERE isAuctionClosed = 0 AND auctionEndDateTime <= getDate()";
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), $getExpiredAuctionsQuery);
        $emailHeaders = 'MIME-Version: 1.0' . "\r\n" .
            'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
            'From: noreply@eenmaalandermaal.nl' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        if($statement !== false) {
            /**
             * @var $items Item[]
             */
            $items = array();
            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $items[] = new Item($databaseHelper, $row['id']);
            }
            if(count($items) >= 1) {
                sqlsrv_query($databaseHelper->getDatabaseConnection(), "{call sp_endAuctions}"); //Call stored procedure to end all the auctions
                foreach ($items as $item) {
                    if($item->getBuyer() !== null) {
                        $alreadyMailed = array($item->getBuyer()->getUsername() => true); //So people who made multiple bids on a item only receive one item.
                        mail($item->getSeller()->getUser()->getMailbox(), "Veiling verlopen", "
                            Uw veiling met de titel <a href='http://iproject16.icasites.nl/product.php?product=". $item->getId() ."'>'". $item->getTitle() ."'</a> is verlopen.<br />"
                            . "Bieder <a href='http://iproject16.icasites.nl/accountOverview.php?user=". $item->getBuyer()->getUsername() ."'>". $item->getBuyer()->getUsername() ."</a> heeft het hoogste bod geboden van &euro;". $item->getSellPrice(), $emailHeaders);
                        mail($item->getBuyer()->getMailbox(), "Veiling verlopen", "
                            De veiling met de titel <a href='http://iproject16.icasites.nl/product.php?product=". $item->getId() ."'>'". $item->getTitle() ."'</a> is verlopen. Gefeliciteerd, u heeft het hoogste bod geboden van &euro;". $item->getSellPrice(), $emailHeaders);
                        $bids = $item->getBids();
                        foreach ($bids as $bid) {
                            if($bid->getUsername() !== $item->getBuyer()->getUsername() && array_key_exists($bid->getUsername(), $alreadyMailed) === false) {
                                $alreadyMailed[$bid->getUsername()] = true;
                                mail($bid->getUser()->getMailbox(), "Veiling verlopen", "
                                    De veiling met de titel <a href='http://iproject16.icasites.nl/product.php?product=". $item->getId() ."'>'". $item->getTitle() ."'</a> is verlopen. Helaas, u heeft niet het hoogste bod geboden. De veiling is gewonnen door:<br />"
                                    . "Bieder: <a href='http://iproject16.icasites.nl/accountOverview.php?user=". $item->getBuyer()->getUsername() ."'>". $item->getBuyer()->getUsername() ."</a><br />"
                                    . "Hoogste bod: &euro;". $item->getSellPrice(), $emailHeaders);
                            }
                        }
                    } else {
                        mail($item->getSeller()->getUser()->getMailbox(), "Veiling verlopen", "
                            Uw veiling met de titel <a href='http://iproject16.icasites.nl/product.php?product=". $item->getId() ."'>'". $item->getTitle() ."'</a> is verlopen, helaas heeft niemand op deze veiling geboden", $emailHeaders);
                    }
                }
            }
        } else {
            $this->HTMLBuilder->addMessage(new Warning($this->HTMLBuilder, "Afhandelen van verlopen velingen mislukt", "Er is een onbekende fout voorgekomen tijdens het afhandelen van afgelopen veilingen"));
        }
    }
}