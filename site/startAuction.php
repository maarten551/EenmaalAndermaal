<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\ImageHelper;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Messages\Warning;
use src\classes\Models\File;
use src\classes\Models\Item;
use src\classes\Models\Rubric;
use src\classes\Models\Seller;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

ini_set("display_errors", 1);

session_start();
date_default_timezone_set("Europe/Amsterdam");

class StartAuction extends Page {
    /**
     * @var Seller
     */
    private $seller;
    public function __construct() {
        parent::__construct("template.html");
        $this->seller = new Seller($this->databaseHelper, $this->loggedInUser);
        if($this->loggedInUser === null || $this->seller->getUser() === null || $this->seller->getActivationCode() !== null) {
            $this->redirectToIndex();
        }
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "start-auction\\form.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $this->generateRubricMenu();
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    public function handleRequestParameters() {
        parent::handleRequestParameters();
        if(array_key_exists("start-auction", $_POST)) {
            $this->addAuction();
        }
    }

    private function addAuction() {
        $registerFields = array(
            "auction-title" => "required",
            "auction-description" => "required",
            "auction-start-price" => "required",
            "auction-duration-days" => "required",
            "auction-town" => "required",
            "auction-country" => "required",
            "auction-payment-method" => "required",
            "auction-rubric" => "required",
            "auction-payment-description" => "optional",
            "auction-shipping-cost" => "optional",
            "auction-shipping-instruction" => "optional"
        );

        if($this->checkAllRequiredFields($registerFields)) {
            $rubric = new Rubric($this->databaseHelper, $_POST['auction-rubric']);
            if($rubric->getName() !== null) {
                if (in_array($_POST['auction-duration-days'], array(1, 3, 5, 7, 10))) {
                    $item = new Item($this->databaseHelper);
                    $item->setSeller(new Seller($this->databaseHelper, $this->loggedInUser));
                    $item->setTitle($_POST['auction-title']);
                    $item->setDescription($_POST['auction-description']);
                    $item->setStartPrice($_POST['auction-start-price']);
                    $item->setAuctionDurationInDays($_POST['auction-duration-days']);
                    $item->setTown($_POST['auction-town']);
                    $item->setCountry($_POST['auction-country']);
                    $item->setPaymentMethod($_POST['auction-payment-method']);
                    $item->setPaymentInstruction($_POST['auction-payment-description']);
                    $item->setShippingCost($_POST['auction-shipping-cost']);
                    $item->setShippingInstruction($_POST['auction-shipping-instruction']);
                    $item->setAuctionStartDateTime(new \DateTime());
                    $item->setAuctionEndDateTime((new \DateTime())->modify("+". $item->getAuctionDurationInDays() ." days"));
                    $item->save();

                    if ($item->getId() !== null) {
                        $this->addImagesToAuction($item);
                        $this->saveRubricRelation($item, $rubric);

                        $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Veiling is toegevoegd", "De veiling van het product is begonnen"));
                    } else {
                        $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Veiling niet toegevoegd", "Er is een onbekent probleem voorgekomen"));
                    }
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Veiling niet toegevoegd", "Er is een niet correct aantal dagen meegegeven"));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Veiling niet toegevoegd", "Een niet bestaande rubric is meegegeven"));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Veiling niet toegevoegd", "Niet alle velden zijn correct ingevuld"));
        }
    }

    /**
     * @param $item Item
     * @param $rubric Rubric
     */
    private function saveRubricRelation($item, $rubric) {
        $itemId = $item->getId();
        $rubricId = $rubric->getId();
        $query = "INSERT INTO itemInRubric (rubricOnLowestLevel, itemId) VALUES (?, ?)";
        $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $query, array(&$rubricId, &$itemId));
        if(sqlsrv_execute($statement) === false) {
            $this->HTMLBuilder->addMessage(new Warning($this->HTMLBuilder, "Geen rubriek gekoppelt", "Er is geen rubriek gekoppelt aan uw veiling, dit kan ervoor zorgen dat gebruikers uw veiling moeilijk kunnen vinden"));
        }
    }

    private function checkAllRequiredFields($registerFields) {
        $checkResult = true;
        foreach ($registerFields as $fieldName => $isRequiredValue) {
            if(!array_key_exists($fieldName, $_POST) || (empty($_POST[$fieldName]) && $isRequiredValue === "required")) {
                $checkResult = false;
                break;
            }
        }

        return $checkResult;
    }

    private function generateRubricMenu() {

        $rubrics = $this->getHighestLevelRubricsWithChildren();

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("rubric-values", $rubrics);
    }

    /**
     * @return Rubric[]
     */
    public function getHighestLevelRubricsWithChildren() {
        $query = "SELECT '<option value='''+ CONVERT(VARCHAR, id)+'''>'+name+'</option>' AS precompiledSelect FROM rubricSelectMenuCache";
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), $query);
        if($statement === false) {
            die( print_r( sqlsrv_errors(), true));
        } else {
            $html = "";
            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $html .= $row['precompiledSelect'];
            }

            return $html; //return root
        }
    }

    /**
     * @param $item Item
     */
    private function addImagesToAuction($item) {
        for($i = 1; $i <= 3; $i++) {
            if($_FILES["image-to-upload-$i"]["size"] > 0) {
                $image = new File($this->databaseHelper, $item->getId() . "-$i.png", $item->getId());
                try {
                    $this->imageUploaded($_FILES["image-to-upload-$i"]["tmp_name"], ImageHelper::$IMAGE_FOLDER_LOCATION . $image->getFileName());
                    $image->save();
                } catch(Exception $e) {
                    $this->HTMLBuilder->addMessage(new Warning($this->HTMLBuilder, "Afbeelding niet opgeslagen", "Afbeelding $i is geen valide afbeelding"));
                }
            }
        }
    }

    /**
     * @param $source
     * @param $target
     * @throws Exception
     * @source http://stackoverflow.com/questions/6484307/how-to-check-if-an-uploaded-file-is-an-image-without-mime-type
     */
    public function imageUploaded($source, $target)
    {
        $sourceImg = @imagecreatefromstring(@file_get_contents($source));
        if ($sourceImg === false)
        {
            throw new Exception("{$source}: Invalid image.");
        }
        $width = imagesx($sourceImg);
        $height = imagesy($sourceImg);
        $targetImg = imagecreatetruecolor($width, $height);
        imagecopy($targetImg, $sourceImg, 0, 0, 0, 0, $width, $height);
        imagedestroy($sourceImg);
        imagepng($targetImg, $target);
        imagedestroy($targetImg);
    }
}

$page = new StartAuction();
echo $page->createHTML();