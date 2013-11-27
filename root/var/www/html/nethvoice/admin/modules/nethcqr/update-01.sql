USE asterisk;
ALTER IGNORE TABLE nethcqr_details ADD COLUMN cod_cli_announcement int(11) after manual_code;

ALTER IGNORE TABLE nethcqr_details ADD COLUMN err_announcement int(11) after cod_cli_announcement;

ALTER IGNORE TABLE nethcqr_details ADD COLUMN ccc_query varchar(8000) after cc_query;

