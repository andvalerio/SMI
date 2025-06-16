Este é um projeto desenvolvido no âmbito da unidade curricular de Sistemas Multimédia para a Internet, onde se utiliza PHP com uma estrutura inspirada em MVC.
A aplicação permite o registo de utilizadores, criação e gestão de álbuns, partilha de fotos e vídeos, comentários, likes e notificações.

---------------

Requisitos

- Container PHP fornecido pelo docente (com PHP, Apache e MySQL já configurados).

- Composer
Na pasta libs instale o composer, via terminal ou pelo website.

Website:
	https://getcomposer.org/download/

Terminal:
	cd 'path para libs'
	composer install


- PHPMailer (instalado via Composer)
Na pasta libs, com o composer, instale o phpmailer via terminal.

	composer require phpmailer/phpmailer

---------------

Como executar a aplicação

1. Transfira o projeto para a pasta Exemplos/PHP.
2. No ficheiro config.xml, modifique a tag server/host para a organização final dos seus ficheiros, para o link dar para a localização da pasta view/auth.
3. Verifique os Requisitos.
4. Abra o index.php via localhost, com o container ligado.
