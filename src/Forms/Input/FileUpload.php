<?php
/*
Olaji SMS – The Smart School Management System
Olaji SMS – Empowering schools with efficient learning and administration tools.
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Forms\Input;

use Gibbon\Services\Format;

/**
 * TextField
 *
 * @version v14
 * @since   v14
 */
class FileUpload extends Input
{
    protected $absoluteURL = '';
    protected $deleteAction = '';

    protected $attachmentName;
    protected $attachmentPath;
    protected $attachments = array();

    protected $canDelete = true;
    protected $maxUpload = true;

    /**
     * Set an array or CSV string of file extensions accepted by this file input.
     * @param   array|string  $accepts
     * @return  self
     */
    public function accepts($accepts)
    {
        if (is_string($accepts)) {
            $accepts = explode(',', $accepts);
        }

        if (!empty($accepts) && is_array($accepts)) {
            $accepts = array_map(function ($str) {
                return trim(strtolower($str), " .'");
            }, $accepts);

            $within = implode(',', array_map(function ($str) {
                return sprintf("'.%s'", $str);
            }, $accepts));

            $this->setAttribute('title', (count($accepts) < 20? implode(', ', $accepts) : ''));
            $this->setAttribute('accept', str_replace("'", '', $within));
            $this->addValidation('Validate.Inclusion', 'within: ['.$within.'], failureMessage: "'.__('Illegal file type!').'", partialMatch: true, caseSensitive: false');
        }
        return $this;
    }

    /**
     * Set the attachment name and path.
     * @param  string $name
     * @param  string  $absoluteURL
     * @param  string  $filePath
     */
    public function setAttachment($name, $absoluteURL, $filePath = '')
    {
        $this->absoluteURL = !empty($absoluteURL)? $absoluteURL.'/' : '';
        $this->attachments[$name] = $filePath;

        return $this;
    }

    /**
     * Set the attachment name and path.
     * @param  string  $absoluteURL
     * @param  array   [ $name => $filePath, ...]
     */
    public function setAttachments($absoluteURL, $attachments)
    {
        $this->absoluteURL = !empty($absoluteURL)? $absoluteURL.'/' : '';
        $this->attachments = array_replace($this->attachments, $attachments);
        return $this;
    }

    /**
     * @deprecated v27 No longer needs separate scripts.
     * Set the URL to visit if the delete action is clicked.
     * @param  string  $actionURL
     */
    public function setDeleteAction($actionURL)
    {
        $this->deleteAction = ltrim($actionURL, '/');

        return $this->canDelete(true);
    }

    /**
     * Set the hidden input MAX_FILE_SIZE in MB and displays the amount (false to disable max upload).
     * @param   string  $value
     * @return  self
     */
    public function setMaxUpload($value)
    {
        $this->maxUpload = $value;

        return $this;
    }

    /**
     * Sets whether the attachment will have a delete option.
     * @param   bool  $value
     * @return  self
     */
    public function canDelete($value)
    {
        $this->canDelete = $value;

        return $this;
    }

    /**
     * Sets whether the input accepts multiple files.
     * @param   bool  $value
     * @return  self
     */
    public function uploadMultiple($value = true)
    {
        $this->setAttribute('multiple', boolval($value));

        if (stripos($this->getName(), '[]') === false) {
            $this->setName($this->getName().'[]');
        }

        return $this;
    }

    /**
     * Returns true if the file upload has attachments
     * @return bool
     */
    protected function hasAttachments()
    {
        return !empty($this->attachments) && !empty(implode(array_values($this->attachments)));
    }

    /**
     * Gets the HTML output for the Maximum file size help-text
     * @return   string
     */
    protected function getMaxUploadText()
    {
        $output = '';
        $hidden = ($this->hasAttachments())? 'display: none;' : '';
        $post = substr(ini_get('post_max_size'), 0, (strlen(ini_get('post_max_size')) - 1));
        $file = substr(ini_get('upload_max_filesize'), 0, (strlen(ini_get('upload_max_filesize')) - 1));
        $label = ($post < $file)? $post : $file;

        if ($this->maxUpload !== true && $this->maxUpload >= 1) {
            $label = ($this->maxUpload < $label)? $this->maxUpload : $label;
            $output .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.(1024 * (1024 * $this->maxUpload)).'">';
        }

        $output .= '<div class="input-box-meta max-upload " style="'.$hidden.'">';
        if ($this->getAttribute('multiple') == true) {
            $output .= sprintf(__('Maximum size for all files: %1$sMB'), $label);
        } else {
            $output .= sprintf(__('Maximum file size: %1$sMB'), $label);
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $output .= '<div class="w-full flex flex-col items-start">';
        
        if (!empty($this->attachments)) {
            // jQuery needs brackets in input names escaped, php needs backslashes escaped = double-escaped
            $idEscaped = str_replace(['[', ']'], ['\\\\[', '\\\\]'], $this->getID());
            

            foreach ($this->attachments as $attachmentName => $attachmentPath) {

                if (!empty($attachmentPath)) {

                    $output .= '<div class="input-box rounded-md w-full">';

                    $output .= '<div class="inline-label text-xs">';
                    $output .= __('Current attachment:').'<br/>';
                    $output .= '<a target="_blank" rel="noopener noreferrer" href="'.$this->absoluteURL.$attachmentPath.'">'.basename($attachmentPath).'</a>';

                    global $session;
                    $absolutePath = $session->get('absolutePath');
                    if (!empty($this->absoluteURL) && (!is_file($absolutePath.'/'.$attachmentPath) || filesize($absolutePath.'/'.$attachmentPath) == 0)) {
                        $output .= Format::tag(__('Error'), 'error ml-2', __('This file is missing or empty. It may have failed to upload or is no longer on the server.'));
                    }

                    $output .= '</div>';

                    $output .=  "<a download title='".__('Download')."' class='inline-button text-gray-600' href='".$this->absoluteURL.$attachmentPath."'>";
                    $output .= icon('solid', 'download', 'size-6 sm:size-5');
                    $output .= '</a>';

                    if ($this->canDelete) {
                        $attachmentNameEscaped = str_replace(['[', ']'], ['\\\\[', '\\\\]'], $attachmentName);
                        if (!empty($this->deleteAction)) {
                            $output .=  "<a title='".__('Delete')."' class='inline-button text-gray-600' href='".$this->absoluteURL.$this->deleteAction."' onclick='return confirm(\"".__('Are you sure you want to delete this record?').' '.__('Unsaved changes will be lost.')."\")'>";
                            $output .= icon('solid', 'delete', 'size-6 sm:size-5');
                            $output .= '</a>';
                        } else {
                            $output .= "<div title='".__('Delete')."' class='inline-button text-gray-600' onclick='if(confirm(\"".__('Are you sure you want to delete this record?').' '.__('Changes will be saved when you submit this form.')."\")) { $(\"#".$attachmentNameEscaped."\").val(\"\"); $(\"#".$idEscaped."\").show(); $(\"#".$idEscaped." + .max-upload\").show(); $(\"#".$idEscaped."\").prop(\"disabled\", false); $(this).parent().detach().remove(); };'>";
                            $output .= icon('solid', 'delete', 'size-6 sm:size-5');
                            $output .= '</div>';
                        }
                    }

                    $output .= '</div>';
                }

                $output .= '<input type="hidden" id="'.$attachmentName.'" name="'.$attachmentName.'" value="'.($attachmentPath ?? '').'">';
            }

            if ($this->getAttribute('multiple') == true) {
                $output .= '<div class="input-box-meta standardWidth right">';
                $output .= '<a onClick="$(\'#'.$idEscaped.'\').show(); $(\'#'.$idEscaped.' + .max-upload\').show(); $(\'#'.$idEscaped.'\').prop(\'disabled\', false);">'.__('Upload File').'</a>';
                $output .= '</div>';
            }

            if ($this->hasAttachments()) {
                $this->setAttribute('style', 'display:none;');
                $this->setAttribute('disabled', 'true');
            }
        }

        $this->addClass('w-full rounded-md bg-white border border-gray-400 font-sans p-2 text-sm text-gray-900  placeholder:text-gray-500');

        $output .= '<input type="file" '.$this->getAttributeString().'>';

        if ($this->maxUpload !== false) {
            $output .= $this->getMaxUploadText();
        }
        $output .= '</div>';

        return $output;
    }
}
