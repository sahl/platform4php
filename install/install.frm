<div class="content">
<h2>Global database</h2>
<text name="global_database_server" label="Global database server host name" required>
<text name="global_database_username" label="Global database user name">
<password name="global_database_password" label="Global database password">
<text name="global_database_name" label="Global database name">

<h2>Local / instance database</h2>
<text name="local_database_server" label="Local database server host name" required>
<text name="local_database_username" label="Local database user name">
<password name="local_database_password" label="Local database password">
<text name="instance_database_name" label="Instance database prefix">

<h2>Directories</h2>
<text name="dir_store" label="Instance store directory" required>
<text name="dir_temp" label="Temporary files directory" required>
<text name="dir_log" label="Global log files directory" required>

<h2>Mail send</h2>
<select name="mail_type" label="Mail server" heading="(None)" value-smtp="SMTP">
<div id="smtp_section">
    <text name="smtp_server" label="Mail server host name">
    <text name="smtp_user" label="Mail server user name">
    <password name="smtp_password" label="Mail server password">
    <text name="smtp_port" label="Mail server port">
</div>

<h2>URLs</h2>
<text name="url_server_talk" label="URL for server talk" required>

<h2>Security</h2>
<text name="password_salt" label="Password salt" required>
<password name="administrator_password" label="Password for admin interface" required>

<h2>Misc</h2>
<checkbox name="debug_mode" label="Debug mode">

<p>
<submit name="save" label="Save & test configuration">
</div>
