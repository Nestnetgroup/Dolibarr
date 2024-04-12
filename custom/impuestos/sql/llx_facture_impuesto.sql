create table llx_facture_impuesto
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  fk_chargesociales        integer NOT NULL,
  fk_facture               integer NOT NULL,
  porcentaje               integer NOT NULL,
  fk_user_author           integer NOT NULL,				  
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP                    		
) ENGINE=innodb;

ALTER TABLE llx_facture_impuesto ADD CONSTRAINT fk_llx_chargesociales   FOREIGN KEY (fk_chargesociales) REFERENCES llx_c_chargesociales (id);
ALTER TABLE llx_facture_impuesto ADD CONSTRAINT fk_llx_facture  FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);