<?php
namespace Platform;

use Platform\Utilities\Database;
use Platform\Server\Job;
use Platform\Utilities\Time;

class Mail extends \Platform\Data\Datarecord {
    
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
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'to_name' => array(
                'label' => 'To (name)',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'to_email' => array(
                'label' => 'To (email)',
                'fieldtype' => self::FIELDTYPE_TEXT
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
            'file_ref' => array(
                'label' => 'Attachments',
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_REFERENCE_MULTIPLE,
                'foreign_class' => 'Platform\\File'
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
        require_once __DIR__.'Mail/src/Exception.php';
        require_once __DIR__.'Mail/src/SMTP.php';
        require_once __DIR__.'Mail/src/PHPMailer.php';
        
    }    
    
    /**
     * Process the outgoing mail queue
     */
    public static function processQueue() {
        $filter = new Filter('\Platform\Mail');
        $filter->addCondition(new ConditionMatch('is_sent', 0));
        $filter->addCondition(new ConditionLesserEqual('scheduled_for', new Time('now')));
        $mails = $filter->execute();
        if ($mails->getCount()) {
            self::initPhpmailer();
            if (!Semaphore::wait('mailqueue_process')) return;
            foreach ($mails->getAll() as $mail) {
                $mailer = new \PHPMailer\PHPMailer\PHPMailer();
                switch (Platform::getConfiguration('mail_type')) {
                    case 'smtp':
                        $mailer->isSMTP();
                        $mailer->Host = Platform::getConfiguration('smtp_server');
                        $mailer->Port = 587;
                        if (Platform::getConfiguration('smtp_user')) {
                            $mailer->SMTPAuth = true;
                            $mailer->Username = Platform::getConfiguration('smtp_user');
                            $mailer->Password = Platform::getConfiguration('smtp_password');
                        }
                    break;
                }
                
                $mailer->CharSet = 'UTF-8';
                $mailer->isHTML($mail->format == self::FORMAT_HTML);
                $mailer->setFrom($mail->from_email, $mail->from_name);
                $mailer->addAddress($mail->to_email, $mail->to_name);
                $mailer->Subject = $mail->subject;
                $mailer->Body = $mail->body;
                $result = $mailer->send();
                $mail->reloadForWrite();
                if (! $result) {
                    $mail->error_count = $this->error_count + 1;
                    // Postpone for an hour
                    $mail->scheduled_for = $this->scheduled_for->add(0,0,1);
                } else {
                    $mail->is_sent = 1;
                    $mail->sent_date = Time::now();
                }
                $mail->save();
            }
            Semaphore::release('mailqueue_process');
            self::setupQueue();
        }
    }

    /**
     * Queue a mail
     * @param string $from_name
     * @param string $from_email
     * @param string $to_name
     * @param string $to_email
     * @param string $subject
     * @param string $body
     * @param array $attachments Pretty file names hashed by file names on disk
     */
    public static function queueMail(string $from_name, string $from_email, string $to_name, string $to_email, string $subject, string $body, array $attachments = array()) {
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
        $mail->save();
        self::setupQueue();
    }
    
    public static function setupQueue() {
        $job = Job::getJob('Platform\\Mail', 'processQueue', Job::FREQUENCY_PAUSED);
        // Check for next run time
        $qr = Database::instanceFastQuery("SELECT MIN(scheduled_for) as next_start FROM ".self::$database_table." WHERE is_sent = 0 AND (error_count < 10 OR error_count IS NULL)");
        if ($qr) {
            $job->next_start = new Time($qr['next_start']);
            $job->frequency = Job::FREQUENCY_ONCE;
        }
        $job->save();
    }
}