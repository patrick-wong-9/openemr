<?php

/**
 * UtilsService holds helper methods for dealing with fhir objects in the services  layer.
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <stephen@nielson.org>
 * @copyright Copyright (c) 2021 Stephen Nielson <stephen@nielson.org>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\FHIR;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\ORDataObject\ContactAddress;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIROperationOutcome;
use OpenEMR\FHIR\R4\FHIRElement\FHIRAddress;
use OpenEMR\FHIR\R4\FHIRElement\FHIRAddressType;
use OpenEMR\FHIR\R4\FHIRElement\FHIRAddressUse;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCodeableConcept;
use OpenEMR\FHIR\R4\FHIRElement\FHIRCoding;
use OpenEMR\FHIR\R4\FHIRElement\FHIRContactPoint;
use OpenEMR\FHIR\R4\FHIRElement\FHIRDateTime;
use OpenEMR\FHIR\R4\FHIRElement\FHIRDecimal;
use OpenEMR\FHIR\R4\FHIRElement\FHIREventTiming;
use OpenEMR\FHIR\R4\FHIRElement\FHIRExtension;
use OpenEMR\FHIR\R4\FHIRElement\FHIRHumanName;
use OpenEMR\FHIR\R4\FHIRElement\FHIRIdentifier;
use OpenEMR\FHIR\R4\FHIRElement\FHIRInteger;
use OpenEMR\FHIR\R4\FHIRElement\FHIRIssueSeverity;
use OpenEMR\FHIR\R4\FHIRElement\FHIRIssueType;
use OpenEMR\FHIR\R4\FHIRElement\FHIRMeta;
use OpenEMR\FHIR\R4\FHIRElement\FHIRNarrative;
use OpenEMR\FHIR\R4\FHIRElement\FHIRPeriod;
use OpenEMR\FHIR\R4\FHIRElement\FHIRPositiveInt;
use OpenEMR\FHIR\R4\FHIRElement\FHIRQuantity;
use OpenEMR\FHIR\R4\FHIRElement\FHIRRatio;
use OpenEMR\FHIR\R4\FHIRElement\FHIRReference;
use OpenEMR\FHIR\R4\FHIRResource\FHIROperationOutcome\FHIROperationOutcomeIssue;
use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
use OpenEMR\FHIR\R4\FHIRElement\FHIRTime;
use OpenEMR\FHIR\R4\FHIRElement\FHIRUnitsOfTime;
use OpenEMR\FHIR\R4\FHIRElement\FHIRUnsignedInt;

use OpenEMR\FHIR\R4\FHIRResource\FHIRDosage;
use OpenEMR\FHIR\R4\FHIRResource\FHIRDosage\FHIRDosageDoseAndRate;
use OpenEMR\FHIR\R4\FHIRResource\FHIRTiming;
use OpenEMR\FHIR\R4\FHIRResource\FHIRTiming\FHIRTimingRepeat;
use OpenEMR\Services\Utils\DateFormatterUtils;


class UtilsService
{
    const UNKNOWNABLE_CODE_NULL_FLAVOR = "UNK";
    const UNKNOWNABLE_CODE_DATA_ABSENT = "unknown";

    public static function createIdentifier($use, $type, $system = null, $value = null, $period = null, $assigner = null): FHIRIdentifier
    {
        $identifier = new FHIRIdentifier();

        if($use !== null) $identifier->setUse(new FHIRCode($use));

        $coding = $type['coding'];
        $codeableConcept = new FHIRCodeableConcept($type);
        foreach ($coding as $c) {
            $codingItem = new FHIRCoding($c);
            $codeableConcept->addCoding($codingItem);
        }
        $identifier->setType($codeableConcept);

        if($system !== null) $identifier->setSystem(new FHIRUri($system));

        // period
        // assigner
        return $identifier;
    }

    public static function createIdentifierFromArray($array): FHIRIdentifier
    {
        $identifier = new FHIRIdentifier();
        $use = $array['use'] ?? [];
        $type = $array['type'] ?? [];
        $system = $array['system'] ?? [];
        $value = $array['value'] ?? [];
        $period = $array['period'] ?? [];
        $assigner = $array['assigner'] ?? [];

        if(!empty($use)) $identifier->setUse(new FHIRCode($use));
        if(!empty($type)){
            $identifier->setType(self::createCodeableConcept($type['coding']));
        }
        if(!empty($system)){
            $identifier->setSystem(new FHIRUri($system));
        }
        if(!empty($value)){
            $identifier->setValue(new FHIRString($value));
        }
        if(!empty($period)){
            $identifier->setPeriod(new FHIRPeriod($period));
        }
        if(!empty($assigner)){
            $identifier->setAssigner(new FHIRReference($assigner));
        }
        return $identifier;
    }

    /*
    * input: FHIRDosageDoseAndRate element
    * return: FHIRDOsageDoseAndRate object
    */
    public static function createDosageDoseAndRateFromArray($array){
        $dosageDoseAndRate = new FHIRDosageDoseAndRate();

        $type = $array['type'] ?? [];
        $doseRange = $array['doseRange'] ?? [];
        $doseQuantity = $array['doseQuantity'] ?? [];
        $rateRatio = $array['rateRatio'] ?? [];
        $rateRange = $array['rateRange'] ?? [];
        $rateQuantity = $array['rateQuantity'] ?? [];
        
        if(!empty($type)) $dosageDoseAndRate->setType(self::createCodeableConcept($type['coding']));
        if(!empty($doseRange)) $dosageDoseAndRate->setDoseRange(self::createRangeFromArray($doseRange));
        if(!empty($doseQuantity)) $dosageDoseAndRate->setDoseQuantity(self::createQuantityFromArray($doseQuantity));
        if(!empty($rateRatio)) $dosageDoseAndRate->setRateRatio(self::createRatioFromArray($rateRatio));
        if(!empty($rateRange)) $dosageDoseAndRate->setRateRange(self::createRangeFromArray($rateRange));
        if(!empty($rateQuantity)) $dosageDoseAndRate->setRateQuantity(self::createQuantityFromArray($rateQuantity));

        return $dosageDoseAndRate;
    }

    public static function createDosageInstructionFromArray($array)
    {
        $dosageInstruction = new FHIRDosage();

        $sequence = $array['sequence'] ?? [];
        $text = $array['text'] ?? [];
        $additionalInstruction = $array['additionalInstruction'] ?? [];
        $patientInstruction = $array['patientInstruction'] ?? [];
        $timing = $array['timing'] ?? [];
        $asNeededBoolean = $array['asNeededBoolean'] ?? [];
        $asNeededCodeableConcept = $array['asNeededCodeableConcept'] ?? [];
        $site = $array['site'] ?? [];
        $route = $array['route'] ?? [];
        $method = $array['method'] ?? [];
        $doseAndRate = $array['doseAndRate'] ?? [];
        $maxDosePerPeriod = $array['maxDosePerPeriod'] ?? [];
        $maxDosePerAdministration = $array['maxDosePerAdministration'] ?? [];
        $maxDosePerLifetime = $array['maxDosePerLifetime'] ?? [];

        if(!empty($sequence)) $dosageInstruction->setSequence(new FHIRInteger($sequence));
        if(!empty($text)) $dosageInstruction->setText(new FHIRString($text));
        if(!empty($additionalInstruction)) {
            $dosageInstruction->addAdditionalInstruction(self::createCodeableConcept($additionalInstruction['coding']));
        }
        if(!empty($patientInstruction)) $dosageInstruction->setPatientInstruction(new FHIRString($patientInstruction));
        if(!empty($timing)) $dosageInstruction->setTiming(self::createTimingFromArray($timing));
        if(!empty($asNeededBoolean)) $dosageInstruction->setAsNeededBoolean(new FHIRBoolean($asNeededBoolean));
        if(!empty($asNeededCodeableConcept)){
            $dosageInstruction->setAsNeededCodeableConcept(self::createCodeableConcept($asNeededCodeableConcept['coding']));
        }
        if(!empty($site)){
            $dosageInstruction->setSite(self::createCodeableConcept($site['coding']));
        }
        if(!empty($route)){
            $dosageInstruction->setRoute(self::createCodeableConcept($route['coding']));
        }
        if(!empty($method)){
            $dosageInstruction->setMethod(self::createCodeableConcept($method['coding']));
        }
        if(!empty($doseAndRate)){
            if(is_array($doseAndRate)){
                foreach($doseAndRate as $item){
                    $dosageInstruction->addDoseAndRate(self::createDosageDoseAndRateFromArray($item));
                }
            } else {
                (new SystemLogger())->error("DOSE AND RATE IS NOT AN ARRAY");
            }
        }
        if(!empty($maxDosePerPeriod)) $dosageInstruction->setMaxDosePerPeriod(self::createRatioFromArray($maxDosePerPeriod));
        if(!empty($maxDosePerAdministration)) $dosageInstruction->setMaxDosePerAdministration(self::createQuantityFromArray($maxDosePerAdministration));
        if(!empty($maxDosePerLifetime)) $dosageInstruction->setMaxDosePerAdministration(self::createQuantityFromArray($maxDosePerLifetime));
    
        return $dosageInstruction;
    }

    public static function createTimingRepeatFromArray($array){
        $timingRepeat = new FHIRTimingRepeat();

        $boundsDuration = $array['boundsDuration'] ?? [];
        $boundsRange = $array['boundsRange'] ?? [];
        $boundsPeriod = $array['boundsPeriod'] ?? [];
        $count = $array['count'] ?? [];
        $countMax = $array['countMax'] ?? [];
        $duration = $array['duration'] ?? [];
        $durationMax = $array['durationMax'] ?? [];
        $durationUnit = $array['durationUnit'] ?? [];
        $frequency = $array['frequency'] ?? [];
        $frequencyMax = $array['frequencyMax'] ?? [];
        $period = $array['period'] ?? [];
        $periodMax = $array['periodMax'] ?? [];
        $periodUnit = $array['periodUnit'] ?? [];
        $dayOfWeek = $array['dayOfWeek'] ?? [];
        $timeOfDay = $array['timeOfDay'] ?? [];
        $when = $array['when'] ?? [];
        $offset = $array['offset'] ?? [];

        if(!empty($boundsDuration)) $timingRepeat->setBoundsDuration(self::createDurationFromArray($boundsDuration));
        if(!empty($boundsRange)) $timingRepeat->setBoundsRange(self::createRangeFromArray($boundsRange));
        if(!empty($boundsPeriod)) $timingRepeat->setBoundsPeriod(self::createPeriodFromArray($boundsPeriod));
        if(!empty($count)) $timingRepeat->setCount(new FHIRPositiveInt($count));
        if(!empty($countMax)) $timingRepeat->setCountMax(new FHIRPositiveInt($countMax));
        if(!empty($duration)) $timingRepeat->setDuration(new FHIRDecimal($duration));
        if(!empty($durationMax)) $timingRepeat->setDurationMax(new FHIRDecimal($durationMax));
        if(!empty($durationUnit)) $timingRepeat->setDurationUnit(new FHIRUnitsOfTime($durationUnit));
        if(!empty($frequency)) $timingRepeat->setFrequency(new FHIRPositiveInt($frequency));
        if(!empty($frequencyMax)) $timingRepeat->setFrequencyMax(new FHIRPositiveInt($frequencyMax));
        if(!empty($period)) $timingRepeat->setPeriod(new FHIRDecimal($period));
        if(!empty($periodMax)) $timingRepeat->setPeriodMax(new FHIRDecimal($periodMax));
        if(!empty($periodUnit)) $timingRepeat->setPeriodUnit(new FHIRUnitsOfTime($periodUnit));
        if(!empty($dayOfWeek)){
            if(is_array($dayOfWeek)){
                foreach($dayOfWeek as $day){
                    $timingRepeat->addDayOfWeek(new FHIRCode($day));
                }
            } else {
                $timingRepeat->addDayOfWeek(new FHIRCode($dayOfWeek));
            }
        }
        if(!empty($timeOfDay)){
            if(is_array($timeOfDay)){
                foreach($timeOfDay as $time){
                    $timingRepeat->addTimeOfDay(new FHIRTime($time));
                }
            } else {
                $timingRepeat->addTimeOfDay(new FHIRTime($time));
            }
        }
        if(!empty($when)){
            if(is_array($when)){
                foreach($when as $time){
                    $timingRepeat->addWhen(new FHIREventTiming($time));
                }
            } else {
                $timingRepeat->addWhen(new FHIREventTiming($time));
            }
        }
        if(!empty($offset)) $timingRepeat->setOffset(new FHIRUnsignedInt($offset));
        
        return $timingRepeat;
    }

    /*
    * input ($array)
    * return FHIRDuration object (wrapper around FHIRQuantity)
    */
    public static function createDurationFromArray($array){
        $duration = new FHIRDuration();

        $value = $array['value'] ?? [];
        $comparator = $array['comparator'] ?? [];
        $unit = $array['unit'] ?? [];
        $system = $array['system'] ?? [];
        $code = $array['code'] ?? [];

        if(!empty($value)) $quantity->setValue(new FHIRDecimal($value));
        if(!empty($comparator)) $quantity->setComparator(new FHIRQuantityComparator($comparator));
        if(!empty($unit)) $quantity->setUnit(new FHIRString($unit));
        if(!empty($system)) $quantity->setSystem(new FHIRUri($system));
        if(!empty($code)) $quantity->setCode(new FHIRCode($code));
        
        return $duration;
    }

    /*
    * input ($array)
    * return FHIRDuration object (wrapper around FHIRQuantity)
    */
    public static function createQuantityFromArray($array){
        $quantity = new FHIRQuantity();
        
        $value = $array['value'] ?? [];
        $comparator = $array['comparator'] ?? [];
        $unit = $array['unit'] ?? [];
        $system = $array['system'] ?? [];
        $code = $array['code'] ?? [];

        if(!empty($value)) $quantity->setValue(new FHIRDecimal($value));
        if(!empty($comparator)) $quantity->setComparator(new FHIRQuantityComparator($comparator));
        if(!empty($unit)) $quantity->setUnit(new FHIRString($unit));
        if(!empty($system)) $quantity->setSystem(new FHIRUri($system));
        if(!empty($code)) $quantity->setCode(new FHIRCode($code));
        
        return $quantity;
    }

    public static function createRatioFromArray($array){
        $ratio = new FHIRRatio();
        $numerator = $array['numerator'] ?? [];
        $denominator = $array['denominator'] ?? [];

        if(!empty($numerator)) $ratio->setNumerator(self::createQuantityFromArray($numerator));
        if(!empty($denominator)) $ratio->setDenominator(self::createQuantityFromArray($denominator));
        return $ratio;
    }

    public static function createRangeFromArray($array){
        $range = new FHIRRange();
        $low = $array['low'] ?? [];
        $high = $array['high'] ?? [];

        if(!empty($low)) $range->setLow(self::createQuantityFromArray($low));
        if(!empty($high)) $range->setLow(self::createQuantityFromArray($high));
        return $range;
    }

    public static function createPeriodFromArray($array){
        $period = new FHIRPeriod();

        $start = $array['start'] ?? [];
        $end = $array['end'] ?? [];

        if(!empty($start)) $period->setStart(new FHIRDateTime($start));
        if(!empty($end)) $period->setStart(new FHIRDateTime($end));
    }

    public static function createTimingFromArray($array){
        $timing = new FHIRTiming();
        $event = $array['event'] ?? [];
        $repeat = $array['repeat'] ?? [];
        $code = $array['code'] ?? [];
        if(!empty($event)){
            if(is_array($event)){
                foreach($event as $e){
                    $timing->addEvent(new FHIRDateTime($e));
                }
            } else {
                $timing->addEvent(new FHIRDateTime($event));
            }
        }
        if(!empty($repeat)) $timing->setRepeat(self::createTimingRepeatFromArray($repeat));
        if(!empty($code)){
            $timing->setCode(self::createCodeableConcept($code['coding']));
        }

        return $timing;
    }

    public static function createRelativeReference($type, $uuid, $displayName = null)
    {
        $reference = new FHIRReference();
        $reference->setType($type);
        $reference->setReference($type . "/" . $uuid);
        if (!empty($displayName) && is_string($displayName)) {
            $reference->setDisplay($displayName);
        }
        return $reference;
    }

    public static function createCanonicalUrlForResource($resourceType, $uuid): FHIRCanonical
    {

        $siteConfig = new ServerConfig();
        $url = $siteConfig->getFhirUrl() . $resourceType . '/' . $uuid;
        $cannonical = new FHIRCanonical();
        $cannonical->setValue($url);
        return $cannonical;
    }

    public static function parseCanonicalUrl(?string $url)
    {
        $parsed_url = [
            'localResource' => false
            ,'validUrl' => false
            ,'resource' => null
            ,'uuid' => null
        ];
        if (empty($url)) {
            return $parsed_url;
        }

        $parsed_url['validUrl'] = true;
        $oauthAddress = (new ServerConfig())->getOauthAddress();
        $oauthHost = parse_url($oauthAddress, PHP_URL_HOST);
        $parts = parse_url($url);
        $parsed_url['localResource'] = $parts['host'] == $oauthHost;
        $splitParts = explode("/", $parts['path']);
        if (count($splitParts) >= 2) {
            $uuid = array_pop($splitParts);
            $resource = array_pop($splitParts);
            $resourceClassCheck = 'OpenEMR\\FHIR\\R4\\FHIRDomainResource\\FHIR' . $resource;
            // we should always be getting canonical urls to a specific resource id, but just in case we are going
            // to
            $idClassCheck = 'OpenEMR\\FHIR\\R4\\FHIRElement\\' . $uuid;
            if (class_exists($resourceClassCheck)) {
                $parsed_url['resource'] = $resource;
                $parsed_url['uuid'] = $uuid;
            } else if (class_exists($idClassCheck)) {
                $parsed_url['resource'] = $uuid; // root level resource at the end
            }
        }
        return $parsed_url;
    }

    public static function getUuidFromReference(FHIRReference $reference)
    {
        $uuid = null;
        if (!empty($reference->getReference())) {
            $parts = explode("/", $reference->getReference());
            $uuid = $parts[1] ?? null;
        }
        return $uuid;
    }

    /*
    * for ex, reference: "Patient/98baaf33-849a-443e-ae28-1b5f58194bce"
    * return "98baaf33-849a-443e-ae28-1b5f58194bce"
    */
    public static function getUuidFromReferenceString(string $reference){
        if($reference == null || strlen($reference) == 0) return "";
        return explode("/", $reference)[1] ?? null;
    }

    public static function createQuantity($value, $unit, $code)
    {
        $quantity = new FHIRQuantity();
        $quantity->setCode($code);
        $quantity->setValue($value);
        $quantity->setUnit($unit);
        $quantity->setSystem(FhirCodeSystemConstants::UNITS_OF_MEASURE);
    }

    public static function createCoding($code, $display, $system): FHIRCoding
    {
        if (!is_string($code)) {
            $code = trim("$code"); // FHIR expects a string
        }
        // make sure there are no whitespaces.
        $coding = new FHIRCoding();
        $coding->setCode(new FHIRCode($code));
        $coding->setDisplay(new FHIRString(trim($display ?? "")));
        $coding->setSystem(new FHIRString(trim($system ?? "")));
        (new SystemLogger())->debug("In UtilsService->createCoding()");

        (new SystemLogger())->debug($coding->getCode()->getValue());

        return $coding;
    }

    public static function createCodeableConcept(array $diagnosisCodes, $defaultCodeSystem = "", $defaultDisplay = ""): FHIRCodeableConcept
    {   

        $diagnosisCode = new FHIRCodeableConcept();
        foreach ($diagnosisCodes as $code => $codeValues) {
            $codeSystem = $codeValues['system'] ?? $defaultCodeSystem;
            if (!empty($codeValues['description'])) {
                $diagnosisCode->addCoding(self::createCoding($code, $codeValues['description'], $codeSystem));
            } else if (!empty($codeValues['display'])){
                $diagnosisCode->addCoding(self::createCoding($code, $codeValues['display'], $codeSystem));
            }
            else {
                $diagnosisCode->addCoding(self::createCoding($code, $defaultDisplay, $codeSystem));
            }
            
        }

        return $diagnosisCode;
    }

    public static function createDataMissingExtension()
    {
        // @see http://hl7.org/fhir/us/core/general-guidance.html#missing-data
        // for some reason in order to get this to work we have to wrap our inner exception
        // into an outer exception.  This might be just a PHPism with the way JSON encodes things
        $extension = new FHIRExtension();
        $extension->setUrl(FhirCodeSystemConstants::DATA_ABSENT_REASON_EXTENSION);
        $extension->setValueCode(new FHIRCode("unknown"));
        $outerExtension = new FHIRExtension();
        $outerExtension->addExtension($extension);
        return $outerExtension;
    }

    public static function createContactPoint($value, $system, $use): FHIRContactPoint
    {
        $fhirContactPoint = new FHIRContactPoint();
        $fhirContactPoint->setSystem($system);
        $fhirContactPoint->setValue($value);
        $fhirContactPoint->setUse($use);
        return $fhirContactPoint;
    }

    public static function createAddressFromRecord($dataRecord): ?FHIRAddress
    {
        $address = new FHIRAddress();
        $addressPeriod = new FHIRPeriod();

        if (!empty($dataRecord['type'])) {
            // TODO: do we want to do any validation on this?  If people add 'types' we will have issues, downside is a code change if we need to support newer standards
            $address->setType(new FHIRAddressType(['value' => $dataRecord['type']]));
        }
        if (!empty($dataRecord['use'])) {
            // TODO: do we want to do any validation on this?  If people add 'uses' we will have issues, downside is a code change if we need to support newer standards
            $address->setUse(new FHIRAddressUse(['value' => $dataRecord['use']]));
        }

        if (!empty($dataRecord['period_start'])) {
            $date = DateFormatterUtils::dateStringToDateTime($dataRecord['period_start']);
            if ($date === false) {
                (new SystemLogger())->errorLogCaller(
                    "Failed to format date record with date format ",
                    ['start' => $dataRecord['period_start'], 'contact_address_id' => ($dataRecord['contact_address_id'] ?? null)]
                );
                $date = new \DateTime('now', new \DateTimeZone(date('P')));
            }
            $addressPeriod->setStart($date->format(\DateTime::RFC3339_EXTENDED));
        } else {
            // we should always have a start period, but if we don't, we will go one year before
            $start = new \DateTime();
            $start->sub(new \DateInterval('P1Y')); // subtract one year
            $addressPeriod->setStart(new FHIRDateTime($start->format(\DateTime::RFC3339_EXTENDED)));
        }

        if (!empty($dataRecord['period_end'])) {
            $date = DateFormatterUtils::dateStringToDateTime($dataRecord['period_end']);
            if ($date === false) {
                (new SystemLogger())->errorLogCaller(
                    "Failed to format date record with date format ",
                    ['date' => $dataRecord['period_end'], 'contact_address_id' => ($dataRecord['contact_address_id'] ?? null)]
                );
                $date = new \DateTime();
            }
            // if we have an end date we need to set our use to be old
            // TODO: when FHIR R4 5.0.1 is the standard, it has proposed to go off the 'end' date instead of the use column for an old address
            // for ONC R4 3.1.1 we have to populate the use column as old (which removes the fact that the address was a 'home' or a 'work' address)
            $addressPeriod->setEnd($date->format(\DateTime::RFC3339_EXTENDED));
            $address->setUse(new FHIRAddressUse(['value' => ContactAddress::USE_OLD]));
        }

        $address->setPeriod($addressPeriod);
        $hasAddress = false;
        $line1 = $dataRecord['line1'] ?? $dataRecord['street'] ?? null;
        if (!empty($line1)) {
            $address->addLine($line1);
            $hasAddress = true;
        }

        $line2 = $dataRecord['line2'] ?? $dataRecord['street_line_2'] ?? null;
        if (!empty($line2)) {
            $address->addLine($line2);
        }

        if (!empty($dataRecord['city'])) {
            $address->setCity($dataRecord['city']);
            $hasAddress = true;
        }
        $district = $dataRecord['county'] ?? $dataRecord['district'] ?? null;
        if (!empty($district)) {
            $address->setDistrict($district);
            $hasAddress = true;
        }

        if (!empty($dataRecord['state'])) {
            $address->setState($dataRecord['state']);
            $hasAddress = true;
        }
        if (!empty($dataRecord['postal_code'])) {
            $address->setPostalCode($dataRecord['postal_code']);
            $hasAddress = true;
        }
        if (!empty($dataRecord['country_code'])) {
            $address->setCountry($dataRecord['country_code']);
            $hasAddress = true;
        }

        if ($hasAddress) {
            return $address;
        }
        return null;
    }

    public static function createFhirMeta($version, $date): FHIRMeta
    {
        $meta = new FHIRMeta();
        $meta->setVersionId($version);
        $meta->setLastUpdated($date);
        return $meta;
    }

    public static function createHumanNameFromRecord($dataRecord): FHIRHumanName
    {
        $name = new FHIRHumanName();
        $name->setUse('official');

        if (!empty($dataRecord['title'])) {
            $name->addPrefix($dataRecord['title']);
        }
        if (!empty($dataRecord['lname'])) {
            $name->setFamily($dataRecord['lname']);
        }

        if (!empty($dataRecord['fname'])) {
            $name->addGiven($dataRecord['fname']);
        }

        if (!empty($dataRecord['mname'])) {
            $name->addGiven($dataRecord['mname']);
        }
        return $name;
    }

    public static function createNullFlavorUnknownCodeableConcept()
    {
        return self::createCodeableConcept([
            self::UNKNOWNABLE_CODE_NULL_FLAVOR => [
                'code' => self::UNKNOWNABLE_CODE_NULL_FLAVOR
                ,'description' => 'unknown'
                ,'system' => FhirCodeSystemConstants::HL7_NULL_FLAVOR
            ]]);
    }

    public static function createDataAbsentUnknownCodeableConcept()
    {
        return self::createCodeableConcept(
            [self::UNKNOWNABLE_CODE_DATA_ABSENT => [
                'code' => self::UNKNOWNABLE_CODE_DATA_ABSENT
                , 'description' => 'Unknown'
                , 'system' => FhirCodeSystemConstants::DATA_ABSENT_REASON_CODE_SYSTEM
            ]]
        );
    }

    /**
     * Given a FHIRPeriod object return an array containing the timestamp in milliseconds of the start and end points
     * of the period.  If the passed in object is null it will return null values for the 'start' and 'end' properties.
     * If the start has no value or if the end period has no value it will return null values for the properties.
     * @param FHIRPeriod $period  The object representing the period interval.
     * @return array Containing two keys of 'start' and 'end' representing the period.
     */
    public static function getPeriodTimestamps(?FHIRPeriod $period)
    {
        $end = null;
        $start = null;
        if ($period !== null) {
            if (!empty($period->getEnd())) {
                $end = strtotime($period->getEnd()->getValue());
            }
            if (!empty($period->getStart())) {
                $start = strtotime($period->getStart()->getValue());
            }
        }
        return [
            'start' => $start,
            'end' => $end
        ];
    }

    public static function createNarrative($message, $status = "generated"): FHIRNarrative
    {
        $div = "<div xmlns='http://www.w3.org/1999/xhtml'>" . $message . "</div>";
        $narrative = new FHIRNarrative();
        $code = new FHIRCode();
        $code->setValue($status);
        $narrative->setStatus($code);
        $narrative->setDiv($div);
        return $narrative;
    }

    public static function getDateFormattedAsUTC(): string
    {
        return (new \DateTime())->format(DATE_ATOM);
    }

    public static function getLocalDateAsUTC($date)
    {
        // make this assumption explicit that we are using the current timezone specified in PHP
        // when we use strtotime or gmdate we get bad behavior when dealing with DST
        // we really should be storing dates internally as UTC instead of local time... but until that happens we have
        // to do this.
        // note this is what we were using before
        // $date = gmdate('c', strtotime($dataRecord['date']));
        // w/ DST the date 2015-06-22 00:00:00 server time becomes 2015-06-22T04:00:00+00:00 w/o DST the server time becomes 2015-06-22T00:00:00-04:00
        $date = new \DateTime($date, new \DateTimeZone(date('P')));
        $utcDate = $date->format(DATE_ATOM);
        return $utcDate;
    }

    public static function createOperationOutcomeSuccess(string $resourceType, int|string $id)
    {
        $operation = self::createOperationOutcomeResource('success', 'success');
        // TODO: if we wanted to put the resource path we would do that here
        return $operation;
    }

    public static function createOperationOutcomeResource(
        $severity_value,
        $code_value,
        $details_value = ''
    ) {
        $resource = new FHIROperationOutcome();
        $issue = new FHIROperationOutcomeIssue();
        $severity = new FHIRIssueSeverity();
        $severity->setValue($severity_value);
        $issue->setSeverity($severity);
        $code = new FHIRIssueType();
        $code->setValue($code_value);
        $issue->setCode($code);
        if ($details_value) {
            $details = new FHIRCodeableConcept();
            $details->setText($details_value);
            $issue->setDetails($details);
        }
        $resource->addIssue($issue);
        return $resource;
    }

    public static function parseReference(?FHIRReference $reference)
    {
        $parsed_reference = [
            'localResource' => false
            ,'uuid' => null
            ,'type' => null
        ];
        if (empty($parsed_reference) || empty($reference->getReference())) {
            return $parsed_reference;
        }

        $oauthAddress = (new ServerConfig())->getOauthAddress();
        $oauthHost = parse_url($oauthAddress, PHP_URL_HOST);
        $parts = parse_url($reference->getReference());

        // if all we have is a path then we skip the host check
        if (isset($parts['host'])) {
            $parsed_reference['localResource'] = $parts['host'] == $oauthHost;
        } else {
            $parsed_reference['localResource'] = true;
        }
        $splitParts = explode("/", $parts['path']);
        if (count($splitParts) >= 2) {
            $parsed_reference['uuid'] = array_pop($splitParts);
            $parsed_reference['type'] = array_pop($splitParts);
        }
        return $parsed_reference;
    }
}
