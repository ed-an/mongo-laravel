# Como criar docker compose com Laravel e MongoDB.


**Laravel:** *� um framework php bem conhecido nada comunidade PHP, ele veio para facilitar a cria��o 
aplica��es web, APIs.*


**MongoDB:** *� um banco de dados NoSQL amplamente usado em aplica��es geolocaliza��o. Com ele voc� pode 
criar bancos de dados que s�o cole��es de documentos semelhantes a JSON.*

O Laravel geralmente � usado com bancos de dados relacionais como MySQL e fornece interfaces, fachadas e m�todos f�ceis para acessar, inserir, atualizar e excluir os dados nele contidos

### Pre-rquisitos
 <ul>
 <li>laravel</li>
 <li>MongoDB</li>
 <li>node</li>
 <li>laravel jensseger</li>
 <li>laravel breeze</li>
 <li>docker-compose</li>
 <li>NoSQLBooter opcional</li>
 </ul>
 
 ## 1 - configura��o do docker compose.

 > ## 1.1 Estrutura do projeto
 
 > workspace
   > - .docker  
	   > -- nginx  <br>
       > -- php <br>
	   > -- Dockerfile
   > - db<br> 
      >-- mongo
   > - src 
 

 
 **__.docker__** - Este diret�rio cont�m todos as configura��es do php e nginx.<br>
      <ul>
      <li> *__nginx__* - Diret�rio com a configura��o nginx.conf</li>
      <li> *__php__* - Diret�rio com a configura��o uploads.ini </li>
       <li> *__Dockefile__*  Cont�m instru��es que criar� uma imagem da aplica��o </li>
    </ul>
 **__db__**  -  Esse  diret�rio ser� mapeado para o container, 
      nele teremos uma subdiret�rio mongo onde os dados ser�o armazenados.
	  
 **__src__** -> Diret�rio da aplica��o, ser� criado pelo composer.

<br><br>

# 1.2 - Criar a estrutura do projeto.


> ```cd workspace ```<br>

> ```mkdir  -p  .docker/nginx```<br>

> ```mkdir  -p  .docker/php```<br>

> ``` touch .docker/Dockerfile  ```<br>

>```mkdir  -p  db/mongo```


>```composer create-project laravel/laravel:^8.0 src```

<br>


# 1.3 - Criar o .docker/Dockerfile
 
 diret�rio: .docker/Dockerfile
``` 
FROM php:7.4-fpm

# Get argument defined in docker-compose file
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    telnetd \
    telnet \
    iputils-ping \
    git \
    ca-certificates \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sudo \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install gd \
    && docker-php-source delete \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Get latest Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

#Install node
ARG NODE_VERSION=16.15.1
ARG NODE_PACKAGE=node-v$NODE_VERSION-linux-x64
ARG NODE_HOME=/opt/$NODE_PACKAGE

ENV NODE_PATH $NODE_HOME/lib/node_modules
ENV PATH $NODE_HOME/bin:$PATH

RUN curl https://nodejs.org/dist/v$NODE_VERSION/$NODE_PACKAGE.tar.gz | tar -xzC /opt/


# Set working directory
WORKDIR /var/www

USER $user 
```


# 1.4 - criar o docker-composer.yml
 
***

```
version: "3.7"
services:
  app:
    build: 
      args: 
        user: $USER #variavel que voc� exportou usando export 
        uid: $IDUSER #IDem
      context: ./.docker
      dockerfile: Dockerfile
    image: laravelapp
    container_name: laravelapp-app
    restart: unless-stopped
    working_dir: /var/www/
    volumes:
      - ./src:/var/www
      - ./.docker/php/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini
    networks:
      - netapp

  mongodb:
    image: "mongo:3.6"
    ports:
     - "27017:27017"
    volumes:
     - ./db/mongo:/data/db
    networks:
     - netapp   

  nginx:
    image: nginx:1.17-alpine
    container_name: laravelapp-nginx
    restart: unless-stopped
    ports:
      - 8000:80
    volumes:
      - ./src:/var/www
      - ./.docker/nginx:/etc/nginx/conf.d
    networks:
      - netapp

networks:
  netapp:
   driver: bridge
```

<br><br/>

**__app__, __nginx__, __mongodb__**: Refere-se ao nome dos servi�os.

**__build__**: Cont�m os argumentos user e uid que acessamos em nosso Dockerfile. 

**__context__**  � o caminho do nosso Dockerfile.

**__dockerfile__**  o nome do arquivo Dockerfile.

**__image:__** cont�m o nome da aplica��o, no nosso caso laravelapp � o nome desta imagem.

**__container_name:__** cont�m o nome do container.<br>

**__restart:__** O container sempre reinicia, at� parar.

**__working_dir__**: define o diret�rio de trabalho padr�o para este cont�iner.

**__volumes:__** � usado para compartilhar os arquivos locais com um determinado 
container. Como voc� pode ver, definimos o volume ***./src:/var/www***, onde o diret�rio ***./src*** cont�m o aplicativo Laravel que ser� compartilhado com o diret�rio do cont�iner ***/var/www***.

**__networks:__** define o servi�o para usar uma rede, ou seja, laravelapp.

# 1.5- Configura��es do php e do nginx.

## .docker/ngnix/ngnix.conf 
***

```
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
```
<br>

Na configura��o  do nginx.conf � importante ficar atento  a diretiva  fastcgi_pass, ele respons�vel por tratar as requisi��es do contexto atual para o backend. no nosso caso o backend �  **app:9000**,   se alterar o nome do servi�o **app**, altere tamb�m na direretiva fastcgi_pass.

##  .docker/php/uploads.ini

***
``` 
file_uploads = On
memory_limit = 64M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 900
```

# 2 - Iniciar a aplica��o

*exportar as vari�veis de ambiente USER e IDUSEr* <br>
``` 
export USER=$(whoami) && export  IDUSER=$(id -u)
```

*subir o docker compose*

```
docker-compose up -d --build
```

Se estiver tudo certo, ao acessar localhost:8000 aparecer� a pagina principal do laravel.

# 2.1 - instalar a biblioteca de intera��o

  A integra��o do MongoDB com o laravel � feito pelo pacote jensseger/mongodb.

  Acesse o  container laravelapp-app atrav�s do comando abaixo:

  ``` 
  docker exec -it laravelapp-app bash
  ```
<br>
  Para instalar rode o comando abaixo:

  ```
  composer require jenssegers/mongodb:^3.8
  ```

# 2.2 Configurar a conex�o

Ap�s instalar o pacote, � necess�rio configurar a conex�o do  servidor mongodb.

Verifique se o mongodb server est� rodando(ping mongodb), altere as configura��es do .env conforme abaixo.

.env 
``` 
DB_CONNECTION=mongodb
## use o nome do servi�o
DB_HOST=mongodb
DB_PORT=27017
DB_DATABASE=blog
DB_USERNAME=
DB_PASSWORD=
```

Edite o arquivo config/database.php e adicione
a conex�o mongodb
``` 
'connections' => [
	....,
	'mongodb' => [
      'driver' => 'mongodb',
      'host' => env('DB_HOST', 'mongodb'),
      'port' => env('DB_PORT', 27017),
      'database' => env('DB_DATABASE', 'blog'),
      'username' => env('DB_USERNAME', ''),
      'password' => env('DB_PASSWORD', '')
    ],
],
```
# 2.3 Alterar a classe User.

Por padr�o o laravel usa o ORM [Eloquent](https://laravel.com/docs/8.x/eloquent) para todos os models, como o Eloquent n�o d� suporte ao mongoDB, precisamos modificar a classe que o model extende. A classe que d� suporte ao mongoDB � a jenssegers/mongodb, instalada anteriormente.

Ela nos permite usar nossos modelos e acessar como far�amos ao usar Mysql ou outros bancos de dados.

Estamos usando somente a classe,  *__user__*. Abra o arquivo  *__app/Model/User.php__*  importe  do pacate Jensserges.

assim:
```
use Jenssegers\Mongodb\Auth\User as Authenticatable;
````

Remova  pacote Illuminate:

```
use Illuminate\Foundation\Auth\User as Authenticatable;
```

Como o User � a clase que pode passar por autentica��o como registrar e fazer login, ele deve estender  Jenssegers\Mongodb\Auth\User.

 A pr�xima mudan�a que precisamos fazer est� relacionada �s datas de atualiza��o. Para usar datas como objetos Carbon, adicione o seguinte dentro da classe User:

```
/**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
protected $dates = ['email_verified_at'];
```

<br>

# 2.4 Adicionar autentica��o:

Vamos adicinar o processo de autentica�o ao website para permitir os clientes registrar e/ou logar. Para fazer vamos utilizar o [laravel's Breeze](https://laravel.com/docs/8.x/starter-kits#laravel-breeze).



O laravel Breeze � uma implementa��o m�nima e simples de todos os recursos de autentica��o do Laravel, incluindo login, registro, redefini��o de senha, verifica��o de e-mail e confirma��o de senha. por tr�s dele � usado (Tailwind CSS)[https://tailwindcss.com/] e (ApineJS)[https://alpinejs.dev/]. 

## 2.4.1 Instalar o Laravel Breeze.

 Dentro do container *laravelapp-app*  rode os comando abaixo um por vez.

 ```
  composer require laravel/breeze:1.9.4

  php artisan breeze:install

  npm install && npm run dev
 ```

 ## 2.4.2 Testar a autentica��o.

 Acesse pelo seu navegador [localhost:8000/register](localhost:8000/register), voc� ver� um formul�rio de cadastro de usu�rio.
 
 Fa�a um cadastro, logo ap�s ser� direcionado para [localhost:8000/dashboard](localhost:8000/dashboard)

 ## Alterar a rota padr�o

 Por padr�o o laravel Breeze redireciona usu�rios autenticado para a rota ```/dashboard```. Vamos alterar para a p�gina inicial.

 abra o arquivo ```routes/web.php``` e adicione a rota abaixo.

 ```
Route::get('/', 'PostController@home')->middleware(['auth'])->name('home');
 ```

 O pr�ximo passo � criar o controle, dentro do container execute o comando abaixo:

 ```
  php artisan make:controller PostController
 ```

Abra o arquivo app/Http/Controllers/PostController.php e adicione o seguinte m�todo, que retorna a view home.

```
public function home() {
    return view('home');
}
```

Acesse  a diret�rio ```resources/views``` Vamos renomear o arquivo dashboard.blade.php Para: 
home.blade.php.

Abra o arquivo app/Providers/RouteServiceProvider.php 
e altere o nome de ```dashboard``` para ```/``` na vari�vel HOME.

Adicione a linha abaixo: 
```
protected $namespace = 'App\Http\Controllers';
```


Agora, quando o usu�rio for para  [localhost:8000](localhost:8000) e n�o estiver logado, ele ser� redirecionado para o formul�rio de login. Se estiverem logados, poder�o acessar o blog.

Vamos alterar o link para home page nos link de navega��o. Acesse o arquivo **resources/views/layouts/navigation.blade.php** e substitua **route('dashbaord')** por **route('home')** em todo o arquivo.
Fa�a o mesmo, substituindo **{{ __('Dashboard') }}** por **{{ __('Home') }}** 

## 2.5  - Criar nosso CRUD
<br>
Nessa parte veremos como criar um novo modelo que � compat�vel com MongoDB e realizaremos as opera��es Create, Read, Update e Delete. 
<br>
### 2.5.1 - criar a migra��o

Criaremos a migra��o que criar� uma nova cole��o de **posts** no banco de dados.

Acesse o container app e rode o comando abaixo:

```
php artisan make:migration create_posts_table
```

Esse comando cria um arquivo no seguinte padr�o **YEAR_MONTH_DAY_TIME_create_posts_table** dentro diretorio  **database/migration/YEAR_MONTH_DAY_TIME_create_posts_table** abra-o e adicione as linhas abaixo:

```
 $table->string('title');
 $table->longText('content');
 $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
```
Isso criar� uma cole��o onde os documentos ter�o os campos **_id**, **title**, **content**, **user_id**, o user_id atuar� como chave estrangeira na tabela posts.

Vamos rodar a migra��o para criar a estrutura no mongoDB.

```
php artisan migrate
```

### 2.5.1 - criar o modelo

Agora vamos criar a classe Post.

``` 
php artisan make:model Post
```

Como fizemos com o modelo User, precisamos alterar a classe  Post extende.

O modelo Post deve extender o pacote Jenssegers, abra o arquivo **app/Models/Post.php** 
remova use **Illuminate\Database\Eloquent\Model;**
e adicione:
``` 
use Jenssegers\Mongodb\Eloquent\Model;
```

Vamos adicione os campos abaixo ao modelo Post.

```
protected $fillable = [
    'title',
    'content',
    'user_id'
];

protected $dates = ['created_at', 'updated_at'];

public function user () {
	return $this->belongsTo(User::class);
}
```

Definimos os campos preench�veis como title, content e user_id. Tamb�m definimos as datas para ser created_at e updated_at. Por fim, adicionamos um relacionamento belongsTo entre Post e User. 


### 2.5.1 - criar a visualiza��o do Post

Vamos criar uma camada para mostrar os dados do model Post, para isso precisamos criar um arquivo em **resources/views/components/post.blade.php** com o seguinte conteuto:

```
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
 <div class="p-6 bg-white border-b border-gray-200">
   <h1 class="text-xl md:text-2xl">{{ $post['title']}}</h1>
   <p class="my-2">{{ $post['content'] }}</p>
   <small class="text-gray-500">{{ $post['user']['name'] }} - {{ $post['created_at'] }}</small>
 </div>
</div>
```
Apenas estamos apresentando o title, content,  name do user e a data de cria��o.

Vamos alterar o arquivo home.blade.php para mostrar todos os posts. abra o arquivo em **resources/views/resources/views/** e substitua o conte�do por:

```
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ __('Home') }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @empty($posts)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            N�o h� postagens
          </div>
        </div>
      @endempty

      @foreach ($posts as $post)
        @component('components.post', ['post' => $post])
        @endcomponent
      @endforeach
    </div>
  </div>
</x-app-layout>
```
Agora, se houver alguma postagem, cada uma delas ser� exibida como cart�es. Se n�o houver nenhuma, a mensagem "N�o h� postagens" ser� exibida.

Finalmente, precisamos passar a vari�vel **$posts** do controller  para a view. 
Abra o arquivo **app/Http/Controllers/PostController.php** e altere o m�todo **home** para o seguinte:

```public function home() {
    $posts = Post::with(['user'])->get();
    return view('home', ['posts' => $posts->toArray()]);
}
```

Com essa implementa��o � poss�vel ler registros do Mongodb da mesma maneira do Mysql usando os mesmo m�todos do Eloquent ORM.

### 2.5.2 - criar e atualizar um Post

**Cadastrar um Post**

Para cadastra um post  precisamos de uma rota, um metodo no controller e uma view. Vamos criar uma rota, :

Abra  o arquivo **routes/web.php** a dicione a rota abaixo
```
Route::get('/posts/create','PostController@createForm')->middleware(['auth'])->name('post.form');
```

Vamos adiconar o m�todo createForm no postController, abra o arquivo app/Http/Controllers/PostController.php

```
public function createForm() {
    return view('post_form');
}
```

Nessa parte vamos criar formul�rio com os campos do post.
crie o arquivo **resources/view/post_form.blade.php**  e adicione o c�digo abaixo:

```
<x-app-layout>
 <x-slot name="header">
   <h2 class="font-semibold text-xl text-gray-800 leading-tight">
     {{ isset($post) ? __('Edit Post') : __('Create Post') }}
   </h2>
 </x-slot>

 <div class="py-12">
   <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
     <div class="p-6 bg-white border-b border-gray-200">

      <!-- Session Status -->

      <x-auth-session-status class="mb-4" :status="session('status')" />

      <!-- Validation Errors -->

      <x-auth-validation-errors class="mb-4" :errors="$errors" />
      
      <form method="POST" action="{{ route('post.save') }}">
       @csrf
       @if (isset($post))
        <input type="hidden" name="id" value="{{ $post->_id }}" />
       @endif
       <div>
        <x-label for="title" :value="__('Title')" />
		<x-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title') ?: (isset($post) ? $post->title : '')" required autofocus />
       </div>
       <div class="mt-3">
        <x-label for="content" :value="__('Content')" />
        <textarea id="content" name="content" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" rows="5">{{ old('content') ?: (isset($post) ? $post->content : '') }}</textarea>
       </div>
       <div class="flex items-center justify-end mt-4">
        <x-button>
          {{ __('Save') }}
        </x-button>
       </div>
      </form>
     </div>
    </div>
   </div>
 </div>
</x-app-layout>
```

Este formul�rio j� est� pronto para ser feito a edi��o.

Com formul�rio pronto, vamos adicionar o link na barra de navega��o.

Abra o arquivo  **resources/views/layouts/navigation.blade.php** 

Abaixo do coment�rio <!-- Navigation Links -->, adicione o  c�digo abaixo:

```
<x-nav-link :href="route('post.form')" :active="request()->routeIs('post.form')">
	                 {{ __('Create Post') }}
</x-nav-link>
```
fa�a o mesmo na div depois de <!-- Responsive Navigation Menu -->
```
 <x-responsive-nav-link :href="route('post.form')" :active="request()->routeIs('post.form')">
	            {{ __('Create Post') }}
            </x-responsive-nav-link>
```

Suba o container, e acesse [localhost:8000](localhost:8000) fa�a login e ver� um link para cadastrar um novo  post, clique nele e veja o formul�rio.

Para salvaro post � precisa criar uma rota no arquivo web e adicionar o m�todo save no postController. abra o arquivo **routes/web.php** e adicione a rota abaixo:

```
Route::post('/posts/create','PostController@save')->middleware(['auth'])->name('post.save');
```

Agora abra o arquivo **app/Http/Controllers/PostController.php**

importe os seguintes pacates:

```
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
```
 Adicione Tamb�m o m�todo save dentro da classe PostController.

```
public function save(Request $request) {

        Validator::validate($request->all(), [
          'id' => 'nullable|exists:posts,_id',
          'title' => 'required|min:1',
          'content' => 'required|min:1'
        ]);

        $user = Auth::user();
        $id = $request->get('id');

        if ($id) {
          $post = Post::query()->find($id);
          if ($post->user->_id !== $user->_id) {
            return redirect()->route('home');
          }
        } else {
          $post = new Post();
          $post->user()->associate($user);
        }

        $post->title = $request->get('title');
        $post->content = $request->get('content');

        $post->save();

        return redirect()->route('home');
      }
```

Esse m�todo valida os campos necess�rio **title** e o **content**

**Editar um Post**

Formul�rio de post j� estava pronto para ser feita a edi��o, no in�cio do c�digo nesse trecho```
**{{ isset($post) ? __('Edit Post') : __('Create Post') }}**
```
se a vari�vel post estiver preenchida ser� mostrado o formul�rio com os campos preenchidos caso contr�rio ser� mostrado o formul�rio.


Crie o arquivo edit.blade.php em **resources/views/components** e adicione o c�digo abaixo. este trecho mostrar o icone de exclus�o.

```
<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
</svg>
```
Vamos agora acrescentar componente de edi��o no arquivo **resources/views/components/post.blade.php**, abra-o acrescente esta parte:

```
@if(\Auth::user()->_id === $post['user']['_id'])
    <a href="{{ route('post.edit.form', ['id' => $post['_id']]) }}" class="inline-block align-middle pb-1 text-decoration-none text-gray-600">
     @component('components.edit')
     @endcomponent
    </a>
   @endif
```

o arquivo completo ficar� assim:
```
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
 <div class="p-6 bg-white border-b border-gray-200">
   <h1 class="text-xl md:text-2xl">{{ $post['title']}}</h1>
   <p class="my-2">{{ $post['content'] }}</p>
   <small class="text-gray-500">{{ $post['user']['name'] }} - {{ $post['created_at'] }}</small>
   @if(\Auth::user()->_id === $post['user']['_id'])
    <a href="{{ route('post.edit.form', ['id' => $post['_id']]) }}" class="inline-block align-middle pb-1 text-decoration-none text-gray-600">
     @component('components.edit')
     @endcomponent
    </a>
   @endif
 </div>
</div>
```

Este trecho adicionar� o link para editar a postagem somente quando a postagem pertencer ao usu�rio atual.

Criaremos uma entrada no arquivo **routes/web.php**
adicione a rota:

```
Route::get('/posts/{id}/edit',  'PostController@editForm')->middleware(['auth'])->name('post.edit.form');
```

acesse o controle **app/Http/Controllers/PostController.php**

acrestente o m�todo editform

```
public function editForm(Request $request, $id) {
    $post = Post::query()->find($id);
    if (!$post) {
      return redirect()->route('home');
    }
    return view('post_form', ['post' => $post]);
  }
```

**Apagar um Post**

Vamos criar um componente para a apagar o um post. Quando

Crie um arquivo **resources/views/components/delete.blade.php**  e acrescente o c�digo abaixo:


```
<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
</svg>
```

Similiar ao editar, adicionaremos no arquivo  
**resources/views/components/post.blade.php** o conte�do abaixo:

```
<form method="POST" action="{{ route('post.delete') }}" class="inline-block align-middle">
	@csrf
	<input type="hidden" name="id" value="{{ $post['_id'] }}" />
	<button type="submit" class="border-0 bg-transparent text-red-400">
		@component('components.delete')
		@endcomponent
	</button>
</form>
```

para finalizar vamos adicionar a rota e o metodo delete.

abra **routes/web.php**
acrescente 
```
Route::post('/posts/delete',  'PostController@delete')->middleware(['auth'])->name('post.delete');
```

para finalizar acrescente ao arquivo **app/Http/Controllers/PostController.php** o m�todo delete.

```
public function delete(Request $request) {
    Validator::validate($request->all(), [
      'id' => 'exists:posts,_id'
    ]);

    $post = Post::query()->find($request->get('id'));
    $post->delete();

    return redirect()->route('home');
  }
```


