create table llx_porcentaje_impuestos
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  fk_chargesociales        integer NOT NULL,
  porcentaje               integer NOT NULL,
  fk_user_author           integer NOT NULL,
  fk_user_modif            integer,				
  date_creation             datetime,		  
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  active                   integer NOT NULL                        		
) ENGINE=innodb;


