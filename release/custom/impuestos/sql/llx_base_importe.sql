create table llx_base_importe
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  campo_facture            varchar(100) NOT NULL,
  nombre                   varchar(100) NOT NULL,
  descripcion              varchar(250)	NOT NULL,		  
  tms                      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP                     		
) ENGINE=innodb;


insert into llx_base_importe(campo_facture ,nombre,descripcion) values ('multicurrency_total_ht','Base imponible','Total de la factura sin IVA');
insert into llx_base_importe(campo_facture ,nombre,descripcion) values ('multicurrency_total_tva','Importe IVA','Total del IVA de la factura');
insert into llx_base_importe(campo_facture ,nombre,descripcion) values ('multicurrency_total_ttc','Importe total','Total de la factura con IVA');

alter table llx_c_chargesociales add base_importe integer  null

