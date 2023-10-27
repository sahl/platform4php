<?php
namespace Platform\Utilities;
/**
 * Datarecord class for storing queueing and sending mails using the PHPMailer library
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=mail_class
 */

use PHPMailer\PHPMailer\PHPMailer;
use Platform\ConditionLesserEqual;
use Platform\ConditionMatch;
use Platform\Datarecord;
use Platform\Filter;
use Platform\Platform;
use Platform\Server\Job;
use Platform\Utilities\Database;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Time;

class Mail extends Datarecord {
    
    const FORMAT_TEXT = 0;
    const FORMAT_HTML = 1;
    
    /**
     * Name of table in database
     * @var string 
     */
    protected static $database_table = 'platform_mails';
    
    /**
     * Set a delete strategy for this object
     * @var int Delete strategy 
     */
    protected static $delete_strategy = self::DELETE_STRATEGY_PURGE_REFERERS;
    
    /**
     * Names of all classes referring this class
     * @var array 
     */
    protected static $referring_classes = array(
        
    );

    /**
     * Indicate if this object is relevant for an instance or globally
     * @var int 
     */
    protected static $location = self::LOCATION_INSTANCE;

    protected static $structure = false;
    protected static $key_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'mail_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'from_name' => array(
                'label' => 'From (name)',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'from_email' => array(
                'label' => 'From (email)',
                'fieldtype' => self::FIELDTYPE_EMAIL
            ),
            'to_name' => array(
                'label' => 'To (name)',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'to_email' => array(
                'label' => 'To (email)',
                'fieldtype' => self::FIELDTYPE_EMAIL
            ),
            'reply_to_name' => array(
                'label' => 'Reply-to (name)',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'reply_to_email' => array(
                'label' => 'Reply-to (email)',
                'fieldtype' => self::FIELDTYPE_EMAIL
            ),
            'subject' => array(
                'label' => 'Subject',
                'is_title' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'body' => array(
                'label' => 'Mail text',
                'columnvisibility' => self::COLUMN_INVISIBLE,
                'fieldtype' => self::FIELDTYPE_BIGTEXT
            ),
            'attachment_data' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_OBJECT,
            ),
            'format' => array(
                'label' => 'Mail format',
                'fieldtype' => self::FIELDTYPE_ENUMERATION,
                'enumeration' => array(self::FORMAT_TEXT => 'Text', self::FORMAT_HTML => 'Html'),
                'default_value' => self::FORMAT_HTML
            ),
            'is_sent' => array(
                'label' => 'Is sent?',
                'fieldtype' => self::FIELDTYPE_BOOLEAN
            ),
            'last_error' => array(
                'label' => 'Last error',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'error_count' => array(
                'label' => 'Error count',
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
            'scheduled_for' => array(
                'label' => 'Scheduled for',
                'fieldtype' => self::FIELDTYPE_DATETIME
            ),
            'sent_date' => array(
                'label' => 'Sent',
                'fieldtype' => self::FIELDTYPE_DATETIME
            )
        );
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
    
    /**
     * Init the PHP mailer library
     */
    public static function initPhpmailer() {
        require_once __DIR__.'/src/Exception.php';
        require_once __DIR__.'/src/SMTP.php';
        require_once __DIR__.'/src/PHPMailer.php';
        
    }    
    
    /**
     * Process the outgoing mail queue
     */
    public static function processQueue() {
        $filter = new Filter(get_called_class());
        $filter->addCondition(new ConditionMatch('is_sent', 0));
        $filter->addCondition(new ConditionLesserEqual('scheduled_for', new Time('now')));
        $filter->addCondition(new ConditionLesserEqual('error_count', 5));
        $mails = $filter->execute();
        if ($mails->getCount()) {
            self::initPhpmailer();
            if (!Semaphore::wait('mailqueue_process')) return;
            foreach ($mails->getAll() as $mail) {
                $mail->reloadForWrite();
                $mailer = new PHPMailer(true);
                try {
                    if (Platform::getConfiguration('mail_type') == 'smtp') {
                        $mailer->isSMTP();
                        $mailer->Host = Platform::getConfiguration('smtp_server');
                        $mailer->Port = Platform::getConfiguration('smtp_port') ?: 587;
                        if (Platform::getConfiguration('smtp_username')) {
                            $mailer->SMTPAuth = true;
                            $mailer->Username = Platform::getConfiguration('smtp_username');
                            $mailer->Password = Platform::getConfiguration('smtp_password');
                        }
                    } else {
                        $mailer->isMail();
                    }
                    $mailer->CharSet = 'UTF-8';
                    $mailer->isHTML($mail->format == self::FORMAT_HTML);
                    $mailer->setFrom($mail->from_email, $mail->from_name);
                    $mailer->addAddress($mail->to_email, $mail->to_name);
                    $mailer->Subject = $mail->subject;
                    $mailer->Body = $mail->body;
                    
                    // Handle reply to
                    if ($mail->reply_to_email) {
                        $mailer->addReplyTo($mail->reply_to_email, $mail->reply_to_name);
                    }
                    
                    // Handle attachments
                    $attachment_data = $mail->attachment_data;
                    if ($attachment_data['attachments']) {
                        foreach ($attachment_data['attachments'] as $file_id) {
                            $file = new \Platform\File();
                            $file->loadForRead($file_id);
                            $res = $mailer->addAttachment($file->getCompleteFilename(), $file->filename);
                        }
                    }
                    if ($attachment_data['inline_attachments']) {
                        foreach ($attachment_data['inline_attachments'] as $identifier => $file_id) {
                            $file = new \Platform\File();
                            $file->loadForRead($file_id);
                            $res = $mailer->addEmbeddedImage($file->getCompleteFilename(), $identifier);
                        }
                    }
                    
                    $result = $mailer->send();
                    $mail->reloadForWrite();
                    if (! $result) {
                        $mail->last_error = Translation::translateForInstance('Could not send mail');
                        $mail->error_count = $mail->error_count + 1;
                        // Postpone for an hour
                        $mail->scheduled_for = Time::now()->add(0,0,1);
                    } else {
                        $mail->is_sent = 1;
                        $mail->sent_date = Time::now();
                    }
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    $mail->last_error = $e->errorMessage();
                    $mail->error_count = $mail->error_count + 1;
                    // Postpone for an hour
                    $mail->scheduled_for = Time::now()->add(0,0,1);
                }

                $mail->save();
            }
            Semaphore::release('mailqueue_process');
            self::setupQueue();
        }
    }

    /**
     * Queue a mail
     * @param string $from_name From name
     * @param string $from_email From email
     * @param string $to_name To name
     * @param string $to_email To email
     * @param string $subject Subject
     * @param string $body Email body
     * @param array $attachments Array of filenames to attach
     * @param array $inlines Array of filenames to add as inline images hashed by inline ID
     */
    public static function queueMail(string $from_name, string $from_email, string $to_name, string $to_email, string $subject, string $body, array $attachments = [], array $inlines = []) : Mail {
        $mail = new Mail(array(
            'from_name' => $from_name,
            'from_email' => $from_email,
            'to_name' => $to_name,
            'to_email' => $to_email,
            'subject' => $subject,
            'body' => $body,
            'file_ref' => $attachments,
            'is_sent' => false,
            'error_count' => 0,
            'format' => self::FORMAT_HTML,
            'scheduled_for' => new Time('now')
        ));
        foreach ($attachments as $filename) {
            $mail->addAttachment($filename);
        }
        foreach ($inlines as $identifier => $filename) {
            $mail->addInlineAttachment($identifier, $filename);
        }
        $mail->save();
        static::setupQueue();
        return $mail;
    }
    
    /**
     * Add an attachment to this mail. The attachment is saved as a separate file
     * @param string $filename
     */
    public function addAttachment(string $filename) {
        $attachment = new \Platform\File();
        $attachment->attachFile($filename);
        $attachment->folder = $this->getAttachmentFolder();
        $attachment->save();
        $attachment_data = $this->attachment_data;
        if (! $attachment_data['attachments']) $attachment_data['attachments'] = [];
        $attachment_data['attachments'][] = $attachment->file_id;
        $this->attachment_data = $attachment_data;
    }
    
    /**
     * Add an inline image to this mail. The image is saved as a separate file
     * @param string $filename
     */
    public function addInlineAttachment(string $identifier, string $filename) {
        $attachment = new \Platform\File();
        $attachment->attachFile($filename);
        $attachment->folder = $this->getAttachmentFolder();
        $attachment->save();
        $attachment_data = $this->attachment_data;
        if (! $attachment_data['inline_attachments']) $attachment_data['inline_attachments'] = [];
        $attachment_data['inline_attachments'][$identifier] = $attachment->file_id;
        $this->attachment_data = $attachment_data;
    }
    
    public function delete(bool $force_purge = false): bool {
        $attachment_data = $this->attachment_data;
        $result = parent::delete($force_purge);
        if ($result) {
            // Delete attachments
            if ($attachment_data['attachments']) {
                foreach ($attachment_data['attachments'] as $file_id) {
                    $file = new \Platform\File();
                    $file->loadForWrite($file_id);
                    $file->delete();
                }
            }
            if ($attachment_data['inline_attachments']) {
                foreach ($attachment_data['inline_attachments'] as $file_id) {
                    $file = new \Platform\File();
                    $file->loadForWrite($file_id);
                    $file->delete();
                }
            }
        }
        return $result;
    }
    
    /**
     * Get the attachment folder for this mail
     * @return string
     */
    public function getAttachmentFolder() : string {
        $attachment_data = $this->attachment_data;
        if ($attachment_data['folder']) return $attachment_data['folder'];
        // Lock to make sure there isn't race conditions
        $semaphore = 'platform_mail_folderdetect';
        if (! Semaphore::wait($semaphore, 5, 10)) trigger_error('Couldn\'t grab attachment folder semaphore within reasonable time.', E_USER_ERROR);
        while (true) {
            $sub_folder = sha1(rand());
            $folder = 'attachments/'.$sub_folder;
            if (!file_exists(\Platform\File::getFullFolderPath($folder))) break;
        }
        \Platform\File::ensureFolderInStore($folder);
        $attachment_data['folder'] = $folder;
        $this->attachment_data = $attachment_data;
        Semaphore::release($semaphore);
        return $attachment_data['folder'];
    }
    
    public static function setupQueue() {
        $job = Job::getJob('Platform\\Utilities\\Mail', 'processQueue', Job::FREQUENCY_PAUSED);
        // Check for next run time
        $qr = Database::getRow(static::query("SELECT MIN(scheduled_for) as next_start FROM ".static::$database_table." WHERE is_sent = 0 AND (error_count < 10 OR error_count IS NULL)"));
        if ($qr['next_start']) {
            $job->next_start = new Time($qr['next_start']);
            $job->frequency = Job::FREQUENCY_ONCE;
        }
        $job->save();
    }
}