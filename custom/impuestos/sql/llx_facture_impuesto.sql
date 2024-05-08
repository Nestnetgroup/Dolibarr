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
/*ALTER TABLE llx_facture_impuesto ADD CONSTRAINT fk_llx_facture  FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);
alter table llx_facture_impuesto drop foreign key fk_llx_facture
*/

create table llx_aplicacion_impuestos
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  codigo                   varchar(5)	NOT NULL,	
  nombre                   varchar(100) NOT NULL,	  
  tms                      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  active                   integer DEFAULT 1 NOT NULL                        		
) ENGINE=innodb;

insert into llx_aplicacion_impuestos(codigo,nombre) values ('VEN','En Venta');
insert into llx_aplicacion_impuestos(codigo,nombre) values ('COM','En Compra');

ALTER TABLE  fk_llx_chargesociales ADD fk_aplicacion_impuestos varchar(5) NULL;



