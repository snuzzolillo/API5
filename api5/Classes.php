<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of API5 RESTful SQLtoJSON                           |
 | Copyright (C) 2017-2018, Santo Nuzzolillo                             |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the LICENSE file for a full license statement.                    |
 |                                                                       |
 | Production                                                            |
 |   Date   : 02/25/2018                                                 |
 |   Time   : 10:44:10 AM                                                |
 |   Version: 0.0.1                                                      |
 +-----------------------------------------------------------------------+
 | Author: Santo Nuzzolilo <snuzzolillo@gmail.com>                       |
 +-----------------------------------------------------------------------+
*/

define("ccsLabel",           1);
define("ccsLink",            2);
define("ccsTextBox",         3);
define("ccsTextArea",        4);
define("ccsListBox",         5);
define("ccsRadioButton",     6);
define("ccsButton",          7);
define("ccsCheckBox",        8);
define("ccsImage",           9);
define("ccsImageLink",       10);
define("ccsHidden",          11);
define("ccsCheckBoxList",    12);
define("ccsDatePicker",      13);
define("ccsReportLabel",     14);
define("ccsReportPageBreak", 15);

$ControlTypes = array(
  "", "Label","Link","TextBox","TextArea","ListBox","RadioButton",
  "Button","CheckBox","Image","ImageLink","Hidden","CheckBoxList",
  "DatePicker", "ReportLabel","ReportPageBreak"
);

define("opEqual",              1);
define("opNotEqual",           2);
define("opLessThan",           3);
define("opLessThanOrEqual",    4);
define("opGreaterThan",        5);
define("opGreaterThanOrEqual", 6);
define("opBeginsWith",         7);
define("opNotBeginsWith",      8);
define("opEndsWith",           9);
define("opNotEndsWith",        10);
define("opContains",           11);
define("opNotContains",        12);
define("opIsNull",             13);
define("opNotNull",            14);
define("opIn",                 15);
define("opBetween",            16);
define("opNotIn",              17);
define("opNotBetween",         18);

define("dsTable",        1);
define("dsSQL",          2);
define("dsProcedure",    3);
define("dsListOfValues", 4);
define("dsEmpty",        5);

define("ccsChecked", true);
define("ccsUnchecked", false);

function CCCheckValue($Value, $DataType)
{
  $result = false;
  if($DataType == ccsInteger)
    $result = is_int($Value); 
  else if($DataType == ccsFloat)
    $result = is_float($Value);
  else if($DataType == ccsDate)
    $result = (is_array($Value) || is_int($Value));
  else if($DataType == ccsBoolean)
    $result = is_bool($Value); 
  return $result;
}

class clsControl
{
  public $ComponentType = "Control";
  public $Errors;
  public $DataType;
  public $DSType;
  public $Format;
  public $DBFormat;
  public $Caption;
  public $ControlType;
  public $ControlTypeName;
  public $Name;
  public $BlockName;
  public $HTML;
  public $Required;
  public $CheckedValue;
  public $UncheckedValue;
  public $State;
  public $BoundColumn;
  public $TextColumn;
  public $Multiple;
  public $Visible;

  public $Page;
  public $Parameters;

  public $CountValue;
  public $SumValue;
  public $ValueRelative;
  public $CountValueRelative;
  public $SumValueRelative;
  public $TotalFunction;
  public $IsPercent = false;
  public $IsEmptySource = false;

  public $isInternal = false;
  public $initialValue;
  public $prevItem = false;

  public $prevValue;
  public $prevCountValue;
  public $prevSumValue;
  public $prevValueRelative;
  public $prevCountValueRelative;
  public $prevSumValueRelative;

  public $Value = "";
  public $Text;
  public $EmptyText;
  public $Values;
  public $IsNull = true;

  public $CCSEvents;
  public $CCSEventResult;

  public $Parent;

  public $Attributes;

  function __construct($ControlType, $Name, $Caption, $DataType, $Format, $InitValue = "", & $Parent)
  {

    global $ControlTypes;

    $this->Text = "";
    $this->Page = "";
    $this->Parameters = "";
    $this->CCSEvents = "";
    $this->Values = "";
    $this->BoundColumn = "";
    $this->TextColumn = "";
    $this->Visible = true;

    $this->Required = false;
    $this->HTML = false;
    $this->Multiple = false;

    $this->Errors = new clsErrors();

    $this->Name = $Name;
    $this->BlockName = $ControlTypes[$ControlType] . " " . $Name;
    $this->ControlType = $ControlType;
    $this->DataType = $DataType;
    $this->DSType = dsEmpty;
    $this->Format = $Format;
    $this->Caption = $Caption;
    if(is_array($InitValue)) {
      $this->Value = $InitValue;
      $this->IsNull = false;
    } else if(!is_null($InitValue))
      $this->SetText($InitValue);
    $this->Parent = & $Parent;
    $this->ComponentType = $ControlTypes[$ControlType];
    $this->Attributes = new clsAttributes($this->Name . ":");
  }

  function clsControl($ControlType, $Name, $Caption, $DataType, $Format, $InitValue = "", & $Parent)
  {
    self::__construct($ControlType, $Name, $Caption, $DataType, $Format, $InitValue, $Parent);
  }

  function Validate()
  {
    global $CCSLocales;
    $validation = true;
    if($this->Required && ($this->Value === "" || is_null($this->Value)) && $this->Errors->Count() == 0)
    {
      $FieldName = strlen($this->Caption) ? $this->Caption : $this->Name;
      $this->Errors->addError($CCSLocales->GetText('CCS_RequiredField', $this->Caption));
    }
    $this->CCSEventResult = CCGetEvent($this->CCSEvents, "OnValidate", $this);
    return ($this->Errors->Count() == 0);
  }

  function GetParsedValue($ParsingValue)
  {
    global $CCSLocales;
    $varResult = "";
    if($this->Multiple && is_array($ParsingValue)) {
      $ParsingValue = $ParsingValue[0];
    }
    if(CCCheckValue($ParsingValue, $this->DataType))
      $varResult = $ParsingValue;
    else if(strlen($ParsingValue))
    {
      switch ($this->DataType)
      {
        case ccsDate:
          $DateValidation = true;
          if (CCValidateDateMask($ParsingValue, $this->Format)) {
            $varResult = CCParseDate($ParsingValue, $this->Format);
            if(!$varResult || !CCValidateDate($varResult))
            {
              $DateValidation = false;
              $varResult = "";
            }
          } else {
            $DateValidation = false;
          }
          if(!$DateValidation && $this->Errors->Count() == 0)
          {
            if (is_array($this->Format)) {
              $FormatString = join("", $this->Format);
            } else {
              $FormatString = $this->Format;
            }
            if (in_array($FormatString, array("ShortDate", "LongDate", "GeneralDate", "LongTime", "ShortTime"))) { 
            	$FormatString = $CCSLocales->GetFormatInfo($FormatString);
            	if (is_array($FormatString)) $FormatString = join("", $FormatString);
            }
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($this->Caption, $FormatString)));
          }
          break;
        case ccsBoolean:
          if (CCValidateBoolean($ParsingValue, $this->Format))
            $varResult = CCParseBoolean($ParsingValue, $this->Format);
          else if($this->Errors->Count() == 0) {
            if (is_array($this->Format)) {
              $FormatString = CCGetBooleanFormat($this->Format);
            } else {
              $FormatString = $this->Format;
            }
              $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFormat', array($this->Caption, $FormatString)));          }
          break;
        case ccsInteger:
          if (CCValidateNumber($ParsingValue, $this->Format))
            $varResult = CCParseInteger($ParsingValue, $this->Format);
          else if($this->Errors->Count() == 0)
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectValue', $this->Caption));
          break;
        case ccsFloat:
          if (CCValidateNumber($ParsingValue, $this->Format))
            $varResult = CCParseFloat($ParsingValue, $this->Format);
          else if($this->Errors->Count() == 0)
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectValue', $this->Caption));
          break;
        case ccsText:
        case ccsMemo:
          $varResult = strval($ParsingValue);
          break;
      }
    }
    return $varResult;
  }

  function GetFormattedValue()
  {
      $strResult = "";
    switch($this->DataType)
    {
      case ccsDate:
        $strResult = CCFormatDate($this->Value, $this->Format);
        break;
      case ccsBoolean:
        $strResult = CCFormatBoolean($this->Value, $this->Format);
        break;
      case ccsInteger:
      case ccsFloat:
      case ccsSingle:
        $strResult = CCFormatNumber($this->Value, $this->Format, $this->DataType);
        break;
      case ccsText:
      case ccsMemo:
        $strResult = strval($this->Value);
        break;
    }
    return $strResult;
  }

  function Prepare()
  {
    if($this->DSType == dsTable || $this->DSType == dsSQL || $this->DSType == dsProcedure)
    {
      if(!isset($this->DataSource->CCSEvents)) $this->DataSource->CCSEvents = "";
      if(!strlen($this->BoundColumn)) $this->BoundColumn = 0;
      if(!strlen($this->TextColumn)) $this->TextColumn = 1;
      $this->EventResult = CCGetEvent($this->DataSource->CCSEvents, "BeforeBuildSelect", $this);
      $this->EventResult = CCGetEvent($this->DataSource->CCSEvents, "BeforeExecuteSelect", $this);
      $FieldName = strlen($this->Caption) ? $this->Caption : $this->Name;
      list($this->Values, $this->Errors) = CCGetListValues($this->DataSource, $this->DataSource->SQL, $this->DataSource->Where, $this->DataSource->Order, $this->BoundColumn, $this->TextColumn, $this->DBFormat, $this->DataType, $this->Errors, $FieldName, $this->DSType);
      $this->DataSource->close();
      $this->EventResult = CCGetEvent($this->DataSource->CCSEvents, "AfterExecuteSelect", $this);
    }
  }

  function Show($RowNumber = "")
  {
    $Tpl = CCGetTemplate($this);
    global $CCSIsXHTML;
    $this->EventResult = CCGetEvent($this->CCSEvents, "BeforeShow", $this);
    
    $BRValue       = $CCSIsXHTML ? "<br />" : "<BR>";
    $CheckedValue  = $CCSIsXHTML ? "checked=\"checked\"" : "CHECKED";
    $SelectedValue = $CCSIsXHTML ? "selected=\"selected\"" : "SELECTED";

    $ControlName = ($RowNumber === "") ? $this->Name : $this->Name . "_" . $RowNumber;
    if($this->Multiple) $ControlName = $ControlName . "[]";

    if(!$this->Visible) {
      $Tpl->SetVar($this->Name . "_Name", $ControlName);
      $Tpl->SetVar($this->Name, "");
      if($Tpl->BlockExists($this->BlockName))
        $Tpl->setblockvar($this->BlockName, "");
      return;
    }

    $this->Attributes->Show();
    
    $MasterPath = CCGetMasterPagePath($this);
    if (strlen($MasterPath)) {
      global $PathToCurrentMasterPage;
      $PathToCurrentMasterPage = $MasterPath;
      $Tpl->SetVar("CCS_PathToMasterPage", $PathToCurrentMasterPage);
    }

    $Tpl->SetVar($this->Name . "_Name", $ControlName);
    switch($this->ControlType)
    {
      case ccsLabel:
        $value=$this->GetText();
        if (!$this->HTML) {
          $value = CCToHTML($value);
          $value = str_replace("\n", $BRValue, $value);
        }
        $Tpl->SetVar($this->Name, $value);
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsReportLabel:
        $value=$this->GetText();
        if (strlen($this->EmptyText) && !strlen($value))
          $value = $this->EmptyText;
        if (!$this->HTML) {
          $value = CCToHTML($value);
          $value = str_replace("\n", $BRValue, $value);
          $value = str_replace("\r", "", $value);
        }
        $Tpl->SetVar($this->Name, $value);
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsTextBox:
      case ccsTextArea:
      case ccsImage:
      case ccsHidden:
        $Tpl->SetVar($this->Name, CCToHTML($this->GetText()));
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsLink:
        if ($this->HTML)
          $Tpl->SetVar($this->Name, $this->GetText());
        else {
          $value = CCToHTML($this->GetText());
          $value = str_replace("\n", $BRValue, $value);
          $Tpl->SetVar($this->Name, $value);
        }
        $Tpl->SetVar($this->Name . "_Src", $this->GetLink());
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsImageLink:
        $Tpl->SetVar($this->Name . "_Src", CCToHTML($this->GetText()));
        $Tpl->SetVar($this->Name, $this->GetLink());
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsCheckBox:
        if($this->Value)
          $Tpl->SetVar($this->Name, $CheckedValue);
        else
          $Tpl->SetVar($this->Name, "");
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsRadioButton:
        $BlockToParse = "RadioButton " . $this->Name;
        $Tpl->SetBlockVar($BlockToParse, "");
        if(is_array($this->Values))
        {
          for($i = 0; $i < sizeof($this->Values); $i++)
          {
            $Value = $this->Values[$i][0];
            $this->Attributes->SetValue("optionNumber", $i + 1);
            $this->Attributes->Objects["optionNumber"]->Show();
            $Text = $this->HTML ? $this->Values[$i][1] : CCToHTML($this->Values[$i][1]);
            $Selected = (CCCompareValues($Value,$this->Value, $this->DataType, $this->Format) == 0) ? $CheckedValue : "";
            $TextValue = CCToHTML(CCFormatValue($Value, $this->Format, $this->DataType, $this->Format));
            $Tpl->SetVar("Value", $TextValue);
            $Tpl->SetVar("Check", $Selected);
            $Tpl->SetVar("Description", $Text);
            $Tpl->Parse($BlockToParse, true);
          }
        }
        break;
      case ccsCheckBoxList:
        $BlockToParse = "CheckBoxList " . $this->Name;
        $Tpl->SetBlockVar($BlockToParse, "");
        if(is_array($this->Values))
        {
          for($i = 0; $i < sizeof($this->Values); $i++)
          {
            $Value = $this->Values[$i][0];
            $this->Attributes->SetValue("optionNumber", $i + 1);
            $this->Attributes->Objects["optionNumber"]->Show();
            $TextValue = CCToHTML(CCFormatValue($Value, $this->Format, $this->DataType));
            $Text = $this->HTML ? $this->Values[$i][1] : CCToHTML($this->Values[$i][1]);
            if ($this->Multiple && is_array($this->Value)) {
              $Selected = "";
              foreach ($this->Value as $Val) {
                if (CCCompareValues($Value,$Val, $this->DataType, $this->Format) == 0) {
                  $Selected = " " . $CheckedValue;
                  break;  
                }
              }
            } else {
              $Selected = (CCCompareValues($Value,$this->Value, $this->DataType, $this->Format) == 0) ? " " .$CheckedValue : "";
            }
            $Tpl->SetVar("Value", $TextValue);
            $Tpl->SetVar("Check", $Selected);
            $Tpl->SetVar("Description", $Text);
            $Tpl->Parse($BlockToParse, true);
          }
        }
        break;
      case ccsListBox:
        $Options = "";
        if(is_array($this->Values))
        {
          for($i = 0; $i < sizeof($this->Values); $i++)
          {
            $Value = $this->Values[$i][0];
            $TextValue = CCToHTML(CCFormatValue($Value, $this->Format, $this->DataType));
            $Text = CCToHTML($this->Values[$i][1]);
            if ($this->Multiple && is_array($this->Value)) {
              $Selected = "";
              foreach ($this->Value as $Val) {
                if (CCCompareValues($Value,$Val, $this->DataType, $this->Format) == 0) {
                  $Selected = " " . $SelectedValue;
                  break;  
                }
              }
            } else {
              $Selected = (CCCompareValues($Value,$this->Value, $this->DataType, $this->Format) == 0) ? " " . $SelectedValue : "";
            }
            $Options .= $CCSIsXHTML 
                        ? "<option value=\"" . $TextValue . "\"" . $Selected . ">" . $Text . "</option>\n"
                        : "<OPTION VALUE=\"" . $TextValue . "\"" . $Selected . ">" . $Text . "</OPTION>\n";
          }
        }
        $Tpl->SetVar($this->Name . "_Options", $Options);
        $Tpl->ParseSafe($this->BlockName, false);
        break;
      case ccsPageBreak:
          $Tpl->SetVar($this->Name, $this->Text);

    }
  }

  function SetValue($Value)
  {
    if($this->ControlType == ccsCheckBox) {
      $this->Value = CCCompareValues($Value, $this->CheckedValue, $this->DataType) == 0 || (CCCompareValues($Value, $this->UncheckedValue, $this->DataType) != 0 && (is_array($Value) || strlen($Value))) ? true : false;
      $this->IsNull = false;
    } else {
      $this->Value = $Value;
      $this->IsNull = is_null($Value);
    }
    $this->Text = $this->GetFormattedValue();
    if (!$this->isInternal) 
      $this->initialValue = $this->Value;
  }

  function SetText($Text, $RowNumber = "")
  {
    $ControlName = ($RowNumber === "") ? $this->Name : $this->Name . "_" . $RowNumber;
    if(CCCheckValue($Text, $this->DataType)) {
      $this->SetValue($Text);
    } else {
      if($this->ControlType == ccsCheckBox) {
        $RequestParameter = CCGetParam($ControlName);
        if (strlen($Text) && strlen($RequestParameter) && $Text == $RequestParameter) {
          $this->Value = true;
    $this->IsNull = false;
        } else {
          $Value = $this->GetParsedValue($Text);
          $this->SetValue($Value);
        }
      } else {
  $this->Text = is_null($Text) ? "" : $Text;
        $this->Value = $this->GetParsedValue($this->Text);
        if (is_null($Text)) {
          $this->Value = "";
          $this->IsNull = true;
        } else {
          $this->IsNull = false;
        }
        if (!$this->isInternal) 
          $this->initialValue = $this->Value;
      }
    }
  }

  function GetValue($returnNull = false)
  {
    if($this->ControlType == ccsCheckBox)
      $value = ($this->Value) ? $this->CheckedValue : $this->UncheckedValue;
    else if($this->Multiple && is_array($this->Value))
      $value = $this->Value[0];
    else
      $value = $returnNull && $this->IsNull ? NULL : $this->Value;

    return $value;
  }

  function GetText()
  {
    if(!strlen($this->Text))
      $this->Text = $this->GetFormattedValue();
    return $this->Text;
  }

  function GetLink()
  {
    global $CCSUseAmp;
    if(CCSubStr($this->Page, 0, 2) == "./") {
      return CCSubStr($this->Page, 2);
    }
    if($this->Parameters == "") {
      return $this->Page;
    } else {
      if (strpos($this->Page, "?") === false) {
        $Delimeter = "?";
      } else {
        $Delimeter = strlen(substr($this->Page, strpos($this->Page, "?") + 1)) == 0 ? "" : "&";
      }
      if ($CCSUseAmp) {
        return str_replace("&", "&amp;", $this->Page . $Delimeter . $this->Parameters);
      } else {
        return $this->Page . $Delimeter . $this->Parameters;
      }
    }
  }

  function SetLink($Link)
  {
    if(!strlen($Link))
    {
      $this->Page = "";
      $this->Parameters = "";
    }
    else
    {
      $LinkParts = explode("?", $Link);
      $this->Page = $LinkParts[0];
      $this->Parameters = (sizeof($LinkParts) == 2) ? $LinkParts[1] : "";
    }
  }

  function GetTotalValue($mode) 
  {
    if ($mode == "GetPrevValue") {
      if ($this->TotalFunction == "Count")
        $this->prevValue += 0;
      $this->Value = $this->prevValue;
      return $this->Value;      
    }
    if ($mode == "GetNextValue" && $this->TotalFunction) {
      if ($this->TotalFunction == "Count")
        $this->prevValue += 0;
      $this->Value = $this->prevValue;
      return $this->Value;      
    }

    $this->Value = $this->initialValue;

    $newVal = $this->prevValue;
    switch ($this->TotalFunction) {
      case "Sum":
        if (strval($this->Value) == "" && strval($this->prevValue) == "")
          break;
        $newVal = $this->Value + $this->prevValue;
        if ($this->IsPercent && (strval($this->Value) != "" || strval($this->prevValueRelative) != ""))
          $this->ValueRelative = $this->Value + $this->prevValueRelative;
        break;
      case "Count":
        $newVal = $this->prevValue + ($this->IsEmptySource || ($this->DataType == ccsBoolean && is_bool($this->Value)) || ($this->DataType == ccsDate  && CCValidateDate($this->Value)) || strval($this->Value) != "" ? 1 : 0);
        if ($this->IsPercent)
          $this->ValueRelative = $this->prevValueRelative + ($this->IsEmptySource || ($this->DataType == ccsBoolean && is_bool($this->Value)) || ($this->DataType == ccsDate  && CCValidateDate($this->Value)) || strval($this->Value) != "" ? 1 : 0);
        break;
      case "Min":
        if (strval($this->Value) == "") 
          break;
        $newVal = strval($this->prevValue) == "" ? $this->Value : min($this->Value,$this->prevValue);
        if ($this->IsPercent)
          $this->ValueRelative = strval($this->prevValueRelative) == "" ? $this->Value : min($this->Value,$this->prevValueRelative);
        break;
      case "Max":
        if (strval($this->Value) == "") 
          break;
        $newVal = strval($this->prevValue) == "" ? $this->Value : max($this->Value,$this->prevValue);
        if ($this->IsPercent)
          $this->ValueRelative = strval($this->prevValueRelative) == "" ? $this->Value : max($this->Value,$this->prevValueRelative);
        break;
      case "Avg":
        if (strval($this->Value) != "") { 
          $this->CountValue = $this->prevCountValue + 1;
          $this->SumValue = $this->prevSumValue + $this->Value;
        }
        if ($this->CountValue == 0) 
          $newVal = $this->prevValue;
        else
          $newVal = $this->SumValue / $this->CountValue;
        if ($this->IsPercent) { 
          if (strval($this->Value) !="") { 
            $this->CountValueRelative = $this->prevCountValueRelative + 1;
            $this->SumValueRelative = $this->prevSumValueRelative + $this->Value;
          }
          if ($this->CountValueRelative == 0)
            $this->ValueRelative = $this->prevValueRelative;
          else
            $this->ValueRelative = $this->SumValueRelative / $this->CountValueRelative;
        }
        break;
      default: 
        if ($mode == "" && $this->IsPercent && (strval($this->Value) != "" || strval($this->prevValueRelative) != "")) {
          $this->ValueRelative = $this->Value + $this->prevValueRelative;
        }
        $newVal = $this->Value;
    }
    $this->Value = $newVal;
    if ($mode == "GetNextValue") {
      return $this->Value;
    }
    $this->prevValueRelative = $this->ValueRelative;
    $this->prevValue = $newVal;
    $this->prevCountValue = $this->CountValue;
    $this->prevSumValue = $this->SumValue;
    $this->prevCountValueRelative = $this->CountValueRelative;
    $this->prevSumValueRelative = $this->SumValueRelative;
    return $this->Value;
  }

  function Reset() 
  {
    $this->Value = "";
    $this->CountValue = "";
    $this->SumValue = "";
    $this->prevValue = "";
    $this->prevCountValue = "";
    $this->prevSumValue = "";
  }

  function ResetRelativeValues() 
  {
    $this->ValueRelative = $this->initialValue;
    $this->prevValueRelative = "";
    $this->CountValueRelative = "";
    $this->SumValueRelative = "";
    $this->prevCountValueRelative = "";
    $this->prevSumValueRelative = "";
  }

}

class clsField
{
  public $DataType;
  public $DBFormat;
  public $Name;
  public $Errors;

  public $Value = "";
  public $IsNull = true;
  public $DBValue = "";

  public function __construct($Name, $DataType, $DBFormat)
  {
        $this->Name = $Name;
    $this->DataType = $DataType;
    $this->DBFormat = $DBFormat;

    $this->Errors = new clsErrors;
  }

  function clsField($Name, $DataType, $DBFormat)
  {
    self::__construct($Name, $DataType, $DBFormat);

  }

  function GetParsedValue()
  {
    global $CCSLocales;
    $varResult = "";

    if (strlen($this->DBValue))
    {
      switch ($this->DataType)
      {
        case ccsDate:
          $DateValidation = true;
          if (CCValidateDateMask($this->DBValue, $this->DBFormat)) {
            $varResult = CCParseDate($this->DBValue, $this->DBFormat);
            if(!$varResult || !CCValidateDate($varResult)) {
              $DateValidation = false;
              $varResult = "";
            }
          } else {
            $DateValidation = false;
          }
          if (!$DateValidation)
          {
            if (is_array($this->DBFormat)) {
              $FormatString = join("", $this->DBFormat);
            } else {
              $FormatString = $this->DBFormat;
            }
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFieldFormat', array($this->Name, $FormatString)));
          }
          break;
        case ccsBoolean:
          if (CCValidateBoolean($this->DBValue, $this->DBFormat)) {
            $varResult = CCParseBoolean($this->DBValue, $this->DBFormat);
          } else {
            if (is_array($this->DBFormat)) {
              $FormatString = CCGetBooleanFormat($this->DBFormat);
            } else {
              $FormatString = $this->DBFormat;
            }
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFieldFormat', array($this->Name, $FormatString)));
          }
          break;
        case ccsInteger:
          if (CCValidateNumber($this->DBValue, $this->DBFormat, true))
            $varResult = CCParseInteger($this->DBValue, $this->DBFormat, true);
          else 
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFieldFormat', array($this->Name, $this->DBFormat)));
          break;
        case ccsFloat:
          if (CCValidateNumber($this->DBValue, $this->DBFormat, true) )
            $varResult = CCParseFloat($this->DBValue, $this->DBFormat, true);
          else 
            $this->Errors->addError($CCSLocales->GetText('CCS_IncorrectFieldFormat', array($this->Name, $this->DBFormat)));
          break;
        case ccsText:
        case ccsMemo:
          $varResult = strval($this->DBValue);
          break;
      }
    }

    return $varResult;
  }

  function GetFormattedValue()
  {
    $strResult = "";
    switch($this->DataType)
    {
      case ccsDate:
        $strResult = CCFormatDate($this->Value, $this->DBFormat);
        break;
      case ccsBoolean:
        $strResult = CCFormatBoolean($this->Value, $this->DBFormat);
        break;
      case ccsInteger:
      case ccsFloat:
      case ccsSingle:
        $strResult = CCFormatNumber($this->Value, $this->DBFormat, $this->DataType, true);
        break;
      case ccsText:
      case ccsMemo:
        $strResult = strval($this->Value);
        break;
    }
    return $strResult;
  }

  function SetDBValue($DBValue)
  {
    $this->DBValue = $DBValue;
    $this->Value = $this->GetParsedValue();
  }

  function SetValue($Value)
  {
    if (is_null($Value)) {
      $this->Value = "";
      $this->IsNull = true;
    } else {
      $this->Value = $Value;
      $this->IsNull = false;
    }
    $this->DBValue = $this->GetFormattedValue();
  }

  function GetValue($returnNull = false)
  {
    return $returnNull && $this->IsNull ? NULL : $this->Value;
  }

  function GetDBValue($returnNull = false)
  {
    return $returnNull && $this->IsNull ? NULL : $this->DBValue;
  }
}

class clsErrors
{
  public $Errors;
  public $ErrorsCount;
  public $ErrorDelimiter;

  public function __construct()
  {
        global $CCSIsXHTML;
    $this->Errors = array();
    $this->ErrorsCount = 0;
    $this->ErrorDelimiter = $CCSIsXHTML ? "<br />" : "<BR>";
  }

  function clsErrors()
  {
    self::__construct();

  }

  function addError($Description)
  {
    if (strlen($Description))
    {
      $this->Errors[$this->ErrorsCount] = $Description; 
      $this->ErrorsCount++;
    }
  }

  function AddErrors($Errors)
  {
    for($i = 0; $i < $Errors->Count(); $i++)
      $this->addError($Errors->Errors[$i]);
  }

  function Clear()
  {
    $this->Errors = array();
    $this->ErrorsCount = 0;
  }

  function Count()
  {
    return $this->ErrorsCount;
  }

  function ToString()
  {

    if(sizeof($this->Errors) > 0)
      return join($this->ErrorDelimiter, $this->Errors);
    else
      return "";
  }

}

class clsLocaleInfo {
  public $FormatInfo;
  public $Name;
  public $Language;
  public $Country;
  public $BooleanFormat;
  public $DecimalDigits;
  public $DecimalSeparator;
  public $GroupSeparator;
  public $MonthNames;
  public $MonthShortNames;
  public $WeekdayNames;
  public $WeekdayShortNames;
  public $WeekdayNarrowNames;
  public $ShortDate;
  public $LongDate;
  public $ShortTime;
  public $LongTime;
  public $GeneralDate;
  public $FirstWeekDay;
  public $OverrideNumberFormats;
  public $AMDesignator;
  public $PMDesignator;
  public $Encoding;
  public $PHPEncoding;
  public $PHPLocale;
  public $Weekend;

  public function __construct($name, $LocaleInfoArray)
  {
        $this->Name = $name;
    $this->Language = $LocaleInfoArray[0];
    $this->Country = $LocaleInfoArray[1];

    $this->BooleanFormat = $LocaleInfoArray[2];

    $this->DecimalDigits = $LocaleInfoArray[3];
    $this->DecimalSeparator = $LocaleInfoArray[4];
    $this->GroupSeparator = $LocaleInfoArray[5];

    $this->MonthNames = $LocaleInfoArray[6];
    $this->MonthShortNames = $LocaleInfoArray[7];

    $this->WeekdayNames = $LocaleInfoArray[8];
    $this->WeekdayShortNames = $LocaleInfoArray[9];
    $this->WeekdayNarrowNames = $LocaleInfoArray[10];

    $this->ShortDate = $LocaleInfoArray[11];
    $this->LongDate = $LocaleInfoArray[12];

    $this->ShortTime = $LocaleInfoArray[13];
    $this->LongTime = $LocaleInfoArray[14];
    $this->AMDesignator = $LocaleInfoArray[15];
    $this->PMDesignator = $LocaleInfoArray[16];

    $this->GeneralDate = array();
    foreach ($this->ShortDate as $val) {
      array_push($this->GeneralDate, $val);
    }
    array_push($this->GeneralDate, ", ");
    foreach ($this->LongTime as $val) {
      array_push($this->GeneralDate, $val);
    }
    $this->FirstWeekDay = $LocaleInfoArray[17];
    $this->OverrideNumberFormats = $LocaleInfoArray[18];
    $this->PHPLocale = $LocaleInfoArray[19];
    $this->Encoding = $LocaleInfoArray[20];
    $this->PHPEncoding = $LocaleInfoArray[21];
    $this->Weekend = isset($LocaleInfoArray[22]) ? $LocaleInfoArray[22] : "";
  }

  function clsLocaleInfo($name, $LocaleInfoArray) {
    self::__construct($name, $LocaleInfoArray);

  }

  function GetInfo($name) {
    return $this->$name;
  }
  
  function GetCCSFormatInfo() {
    if (!$this->FormatInfo)
      $this->FormatInfo = join("|" , Array($this->Name, $this->Language, $this->Country,  join(";", $this->BooleanFormat),
        $this->DecimalDigits, $this->DecimalSeparator, $this->GroupSeparator,
        join(";", $this->MonthNames) ,  join(";", $this->MonthShortNames),
        join(";", $this->WeekdayNames), join(";", $this->WeekdayShortNames),
        join("", $this->ShortDate), join("", $this->LongDate),
        join("", $this->ShortTime), join("", $this->LongTime),       
        $this->FirstWeekDay, $this->AMDesignator, $this->PMDesignator));
    return $this->FormatInfo;
  }
}

class clsLocale {
  public $Name;
  public $Dir;
  public $Ext = ".txt";
  public $ParentLocale;
  public $ParentLocaleName = "";
  public $IsLoaded = false;
  public $LocaleInfo;
  public $Messages;
  public $InternalEncoding = "UTF-8";

  public function __construct($name, $LocaleInfoArray, $dir = "")
  {
        $this->Name = $name;
    $this->Dir = $dir;
    $this->Translations = array();
    $this->LocaleInfo = new clsLocaleInfo($name, $LocaleInfoArray);
    $arr = explode("-", $name, 2);
    if (count($arr) == 2)
      $this->ParentLocaleName = $arr[0];
  }

  function clsLocale($name, $LocaleInfoArray, $dir = "") {
    self::__construct($name, $LocaleInfoArray, $dir);
  }

  function LoadTranslation($filename = "") {
    $this->Messages = array();
    if ($filename == "")
      $filename = $this->Name . $this->Ext;
    if (CCSubStr($filename, 0, 1) != "/" && CCSubStr($filename, 0, 1) != ".")
      $filename = $this->Dir . "/" . $filename;
    if ($FileContent = @file($filename)) {
      foreach($FileContent as $str) {
        if (preg_match("/^([^'].+?)=(.*)$/", $str, $matches)) { 
          $this->Messages[strtolower($matches[1])] = str_replace(chr(13), "", $matches[2]);
        }
      }
    }
    $this->IsLoaded = true;
  }

  function GetMessage($originalId, $parent = "") {
    global $CCSLocales;
    global $FileEncoding;
    $id = strtolower($originalId);
    if ($id == "ccs_localeid") return $this->Name;
    if ($id == "ccs_languageid") return $this->LocaleInfo->GetInfo("Language");
    if ($id == "ccs_formatinfo") return $this->LocaleInfo->GetCCSFormatInfo();
    
    if (!$this->IsLoaded)
      $this->LoadTranslation();
    if (array_key_exists($id,  $this->Messages)) {
      return $FileEncoding != $this->InternalEncoding && $id != "ccs_formatinfo" ? CCConvertEncoding($this->Messages[$id], $this->InternalEncoding, $FileEncoding) : $this->Messages[$id];
    } else if (strtolower($parent) == strtolower($CCSLocales->DefaultLocale)) {
      return $originalId;
    } else if ($this->ParentLocale) {
      return $this->ParentLocale->GetMessage($id, $this->Name);
    } elseif ($this->ParentLocaleName && array_key_exists($this->ParentLocaleName, $CCSLocales->Locales)) {
      $this->ParentLocale = & $CCSLocales->Locales[$this->ParentLocaleName];
      return $this->ParentLocale->GetMessage($id, $this->Name);
    } elseif (strtolower($CCSLocales->DefaultLocale) != strtolower($this->Name)) {
      $DefaultLocale = $CCSLocales->Locales[$CCSLocales->DefaultLocale];
      return $DefaultLocale->GetMessage($id, $this->Name);
    } else {
      return $originalId;
    }

  }
}

class clsLocales {
  public $Locale;
  public $DefaultLocale;
  public $Locales;
  public $Dir;

  public function __construct($dir, $locale = "")
  {
            $this->Dir = $dir;
    $this->Locale = $locale;     $this->DefaultLocale = "";
    $this->Locales = array();
      }

  function clsLocales($dir, $locale = "")  {
    self::__construct($dir, $locale);

  }

  function Init() {
    $this->SetLocale(CCGetFromGet("locale"));
    $this->SetLocale(CCGetSession("locale"));
    $this->SetLocale($this->DefaultLocale);
    CCSetSession("locale", $this->GetFormatInfo("Name"));
    CCSetSession("lang", $this->GetFormatInfo("Language"));
    $this->Locales = is_array($this->Locales) ? $this->Locales : array();
  }

  function AddLocale($name, $LocaleInfoArray) {
    $lname = strtolower($name);
    if (array_key_exists($lname, $this->Locales))
      return;
    $this->Locales[$lname] = new clsLocale($name, $LocaleInfoArray, $this->Dir);
  }

  function GetText($id, $params = Null, $locale = "") {
    if ($locale == "")  
      $locale = $this->Locale;
    if ($locale == "")  
      $locale = $this->DefaultLocale;
    if (!array_key_exists($locale, $this->Locales))
      return "";
    $Result = $this->Locales[$locale]->GetMessage($id);
    if ($Result != "") {
      $Result = preg_replace("/\\\\n/", "\n", $Result);
      $Result = preg_replace("/\\\\/", "\\", $Result);
      if (is_array($params)) {
        for ($i = 0; $i < count($params); $i++)
          $Result = preg_replace("/\{$i\}/", $params[$i], $Result);
      } elseif (!is_null($params)) {
          $Result = preg_replace("/\{0}/", $params, $Result);
      }
    }
    return $Result;
  }

  function GetFormatInfo($name, $locale = "") {
    if ($locale == "")  
      $locale = $this->Locale;
    if ($locale == "")  
      $locale = $this->DefaultLocale;
    return $this->Locales[$locale]->LocaleInfo->GetInfo($name);
  }

  function cmp($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
  }

  function FindLocale($locale) {
    $locale = strtolower($locale);
    if (!$this->Locale && $locale) {
      $arr = explode("-", $locale, 2);        
      $lang = $arr[0];
      $country = isset($arr[1]) ? $arr[1] : "";
      $defaultCountry = array_key_exists($lang, $this->Locales) ? strtolower($this->Locales[$lang]->LocaleInfo->GetInfo("Country")) : "";
      if (!$country && $defaultCountry && array_key_exists($lang . "-" . $defaultCountry, $this->Locales)) 
        return $lang . "-" . $defaultCountry;
      elseif ($country && !array_key_exists($locale, $this->Locales) && array_key_exists($lang . "-" . $defaultCountry, $this->Locales)) 
        return $lang . "-" . $defaultCountry;
      elseif (array_key_exists($locale, $this->Locales))
        return $locale;
      elseif (array_key_exists($lang, $this->Locales))
        return $lang;
    }
    return false;
  }

  function SetLocale($locale) {
    if (!$this->Locale && $locale) {
      $this->Locale = $this->FindLocale($locale);
      if (!$this->Locale) 
        $this->Locale = $this->DefaultLocale;
    }
  }

  function  SetLocaleFromHttpHeader($Name = "HTTP_ACCEPT_LANGUAGE") {
    if ($this->Locale)
      return false;
    $Locales = array();
    $locale = "";
    $q = "";
    if (!isset($_SERVER[$Name])) return;
    $arr = explode(",", strtolower($_SERVER[$Name]));
    foreach ($arr as $L) {
      if(preg_match("/(.+);q=(\\d+(\\.\\d+)?)/", $L, $matches)) {
        $locale = $matches[1];
        $q = doubleval($matches[2]);
      } else {
        $locale = $L;
        $q = 1;
      }
      if (!array_key_exists(strval($q), $this->Locales))
        $Locales[strval($q)] = array();
      array_push($Locales[strval($q)], $locale);
    }
    uksort($Locales, array($this, "cmp"));

    foreach ($Locales as $q) {
      foreach ($q as $locale) {
        if ($result = $this->FindLocale($locale)) {
          $this->Locale = $result;
          return;
        }
      }
    }
  }

}

class clsMainPage
{
  public $ComponentType = "Page";
  public $Parent = false;
  public $Connections = array();
  public $Attributes = array();
}

class clsAttribute {
  public  $ComponentType = "Attribute";
  public  $DataType = ccsText;
  public  $Format = "";
  public  $Name = "";
  public  $Prefix = "";

  public  $Value;
  public  $Text;

  public function __construct($Name, $Prefix, $DataType="", $Format = "")
  {
        $this->Name = $Name;
    $this->Prefix = $Prefix;
    if ($this->DataType)
      $this->DataType = $DataType;
    $this->Format = $Format;
  }

  function clsAttribute($Name, $Prefix, $DataType="", $Format = "") {
    self::__construct($Name, $Prefix, $DataType, $Format);

  }

  function GetParsedValue($ParsingValue, $MaskFormat) {
    return CCParseValue($ParsingValue, $MaskFormat, $this->DataType, "", "");
  }

  function GetFormattedValue($MaskFormat) {
      return CCFormatValue($this->Value, $MaskFormat, $this->DataType);
  }  

  function Show() {
    $Tpl = CCGetTemplate();
    $Tpl->SetVar($this->Prefix . $this->Name, $this->GetText());
  }

  function SetValue($NewValue) {
    $this->Text = null;
    $this->Value = $NewValue;
  }

  function GetValue() {
    return $this->Value;
  }

  function SetText($NewText) {
    $this->Text = $NewText;
    $this->Value = $this->GetParsedValue($NewText, $this->Format);
  }

  function GetText() {
    if (is_null($this->Text))
      $this->Text = $this->GetFormattedValue($this->Format);
    return $this->Text;
  }

}

class clsAttributes {
  public  $ComponentType = "Attributes";
  public $Objects = array();
  public $Block = "";
  public $Accumulate = "";
  public $Prefix = "";

  public function __construct($Prefix)
  {
        $this->Prefix = $Prefix;
  }

  function clsAttributes($Prefix) {
    self::__construct($Prefix);

  }

  function Add(& $Attr) {
    $this->Objects[$Attr->Name] = & $Attr;
  }

  function AddAttribute($Name, $DataType = "", $Format = "") {
    $this->Objects[$Name] = new clsAttribute($Name, $this->Prefix, $DataType, $Format);
  }

  function GetValue($Name) {
    return array_key_exists($Name, $this->Objects) ? $this->Objects[$Name]->GetValue() : "";
  }

  function GetText($Name) {
    return array_key_exists($Name, $this->Objects) ? $this->Objects[$Name]->GetText() : "";
  }

  function SetValue($Name, $NewValue, $DataType = "", $Format = "") {
    if (!array_key_exists($Name, $this->Objects))
      $this->AddAttribute($Name, $DataType, $Format);
    $this->Objects[$Name]->SetValue($NewValue);
  }

  function SetText($Name, $NewText) {
    if (!array_key_exists($Name, $this->Objects))
      $this->AddAttribute($Name);
    $this->Objects[$Name]->SetText($NewText);
  }

  function Show() {
    foreach ($this->Objects as $Name => $Attribute) 
        $this->Objects[$Name]->Show();
  }

  function Clear() {
    $this->Objects = array();
  }

  function GetAsArray() {
    $arr = array();
    foreach ($this->Objects as $Name => $Value) {
      $arr[$Name] = array($this->Objects[$Name]->GetValue(), $this->Objects[$Name]->GetText(), $this->Objects[$Name]->DataType, $this->Objects[$Name]->Format);
    }
    $arr["."] = $this->Prefix;
    return $arr;
  }

  function RestoreFromArray($Arr) {
    $this->Objects = array();
    $this->Prefix = $Arr["."];
    $this->AddFromArray($Arr);
  }

  function AddFromArray($Arr) {
    foreach ($Arr as $Name => $Value) {
      if ($Name != ".") {
        $this->Objects[$Name] = new clsAttribute($Name, $this->Prefix, $Value[2], $Value[3]);
        $this->Objects[$Name]->Value = $Value[0];
        $this->Objects[$Name]->Text = $Value[1];
      }
    }
  }

}

class clsMasterPageTemplate {

  public $Redirect;
  public $Tpl;
  public $HTMLTemplate;
  public $TemplateFileName;
  public $ComponentName;
  public $Attributes;
  public $HTML;
  
  public $CCSEvents;
  public $CCSEventResult;
  
  public $Visible;
  public $Page;
  public $Name;
  public $CacheAction;
  public $TemplateSource = "";
  public $TemplatePathValue;

  public function __construct()
  {
        $this->Visible = true;
    $this->Redirect = "";
  }

  function clsMasterPageTemplate() {
    self::__construct();

  }
  
  function Operation() {
    if (!$this->Visible) return;
  }
  
  function Initialize($Name, $Path) {
    $this->Name = $Name;
    $this->TemplatePathValue = $Path;
    if (!$this->Visible) return;
  }
  
  function InitializeTemplate() {
    global $PathToRoot, $Tpl, $TemplateEncoding;
    $this->HTMLTemplate = new clsTemplate();
    if (!strlen($this->TemplateSource)) {
      $this->HTMLTemplate->LoadTemplate($this->TemplatePathValue . $this->TemplateFileName, "main", $TemplateEncoding);
    } else {
      $this->HTMLTemplate->LoadTemplateFromStr($this->TemplateSource, "main", $TemplateEncoding);
    }
    $this->HTMLTemplate->SetVar("CCS_PathToRoot", $PathToRoot);
    $this->HTMLTemplate->block_path = "/main";
    $this->CCSEventResult = CCGetEvent($this->CCSEvents, "OnInitializeView", $this);
  }
  
  function Show() {
    $this->CCSEventResult = CCGetEvent($this->CCSEvents, "BeforeShow", $this);
    if (!$this->Visible) return;
    $this->HTMLTemplate->SetVar("CCS_PathToCurrentPage", RelativePath . $this->TemplatePathValue);
    $this->HTMLTemplate->SetVar("page:pathToCurrentPage", RelativePath . $this->TemplatePathValue);
    $this->Attributes->SetValue("PathToCurrentPage", RelativePath . $this->TemplatePathValue);
    $this->Attributes->Show();
    $this->HTMLTemplate->block_path = "";
    $this->HTMLTemplate->parse("main", false);
    $this->HTML = $this->HTMLTemplate->GetVar("main");
  }
  
}

?>
