# Como criar docker compose com Laravel e MongoDB.


**Laravel:** *É um framework php bem conhecido nada comunidade PHP, ele veio para facilitar a criação 
aplicações web, APIs.*


**MongoDB:** *é um banco de dados NoSQL amplamente usado em aplicações geolocalização. Com ele você pode 
criar bancos de dados que são coleções de documentos semelhantes a JSON.*

O Laravel geralmente é usado com bancos de dados relacionais como MySQL e fornece interfaces, fachadas e métodos fáceis para acessar, inserir, atualizar e excluir os dados nele contidos

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
 
 ## 1 - configuração do docker compose.

 > ## 1.1 Estrutura do projeto
 
 > workspace
   > - .docker  
	   > -- nginx  <br>
       > -- php <br>
	   > -- Dockerfile
   > - db<br> 
      >-- mongo
   > - src 
 

 
 **__.docker__** - Este diretório contém todos as configurações do php e nginx.<br>
      <ul>
      <li> *__nginx__* - Diretório com a configuração nginx.conf</li>
      <li> *__php__* - Diretório com a configuração uploads.ini </li>
       <li> *__Dockefile__*  Contém instruções que criará uma imagem da aplicação </li>
    </ul>
 **__db__**  -  Esse  diretório será mapeado para o container, 
      nele teremos uma subdiretório mongo onde os dados serão armazenados.
	  
 **__src__** -> Diretório da aplicação, será criado pelo composer.

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
 
 diretório: .docker/Dockerfile
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
        user: $USER #variavel que você exportou usando export 
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

**__app__, __nginx__, __mongodb__**: Refere-se ao nome dos serviços.

**__build__**: Contém os argumentos user e uid que acessamos em nosso Dockerfile. 

**__context__**  é o caminho do nosso Dockerfile.

**__dockerfile__**  o nome do arquivo Dockerfile.

**__image:__** contém o nome da aplicação, no nosso caso laravelapp é o nome desta imagem.

**__container_name:__** contém o nome do container.<br>

**__restart:__** O container sempre reinicia, até parar.

**__working_dir__**: define o diretório de trabalho padrão para este contêiner.

**__volumes:__** é usado para compartilhar os arquivos locais com um determinado 
container. Como você pode ver, definimos o volume ***./src:/var/www***, onde o diretório ***./src*** contém o aplicativo Laravel que será compartilhado com o diretório do contêiner ***/var/www***.

**__networks:__** define o serviço para usar uma rede, ou seja, laravelapp.

# 1.5- Configurações do php e do nginx.

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

Na configuração  do nginx.conf é importante ficar atento  a diretiva  fastcgi_pass, ele responsável por tratar as requisições do contexto atual para o backend. no nosso caso o backend é  **app:9000**,   se alterar o nome do serviço **app**, altere também na direretiva fastcgi_pass.

##  .docker/php/uploads.ini

***
``` 
file_uploads = On
memory_limit = 64M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 900
```

# 2 - Iniciar a aplicação

*exportar as variáveis de ambiente USER e IDUSEr* <br>
``` 
export USER=$(whoami) && export  IDUSER=$(id -u)
```

*subir o docker compose*

```
docker-compose up -d --build
```

Se estiver tudo certo, ao acessar localhost:8000 aparecerá a pagina principal do laravel.

# 2.1 - instalar a biblioteca de interação

  A integração do MongoDB com o laravel é feito pelo pacote jensseger/mongodb.

  Acesse o  container laravelapp-app através do comando abaixo:

  ``` 
  docker exec -it laravelapp-app bash
  ```
<br>
  Para instalar rode o comando abaixo:

  ```
  composer require jenssegers/mongodb:^3.8
  ```

# 2.2 Configurar a conexão

Após instalar o pacote, é necessário configurar a conexão do  servidor mongodb.

Verifique se o mongodb server está rodando(ping mongodb), altere as configurações do .env conforme abaixo.

.env 
``` 
DB_CONNECTION=mongodb
## use o nome do serviço
DB_HOST=mongodb
DB_PORT=27017
DB_DATABASE=blog
DB_USERNAME=
DB_PASSWORD=
```

Edite o arquivo config/database.php e adicione
a conexão mongodb
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

Por padrão o laravel usa o ORM [Eloquent](https://laravel.com/docs/8.x/eloquent) para todos os models, como o Eloquent não dá suporte ao mongoDB, precisamos modificar a classe que o model extende. A classe que dá suporte ao mongoDB é a jenssegers/mongodb, instalada anteriormente.

Ela nos permite usar nossos modelos e acessar como faríamos ao usar Mysql ou outros bancos de dados.

Estamos usando somente a classe,  *__user__*. Abra o arquivo  *__app/Model/User.php__*  importe  do pacate Jensserges.

assim:
```
use Jenssegers\Mongodb\Auth\User as Authenticatable;
````

Remova  pacote Illuminate:

```
use Illuminate\Foundation\Auth\User as Authenticatable;
```

Como o User é a clase que pode passar por autenticação como registrar e fazer login, ele deve estender  Jenssegers\Mongodb\Auth\User.

 A próxima mudança que precisamos fazer está relacionada às datas de atualização. Para usar datas como objetos Carbon, adicione o seguinte dentro da classe User:

```
/**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
protected $dates = ['email_verified_at'];
```

<br>

# 2.4 Adicionar autenticação:

Vamos adicinar o processo de autenticaão ao website para permitir os clientes registrar e/ou logar. Para fazer vamos utilizar o [laravel's Breeze](https://laravel.com/docs/8.x/starter-kits#laravel-breeze).



O laravel Breeze é uma implementação mínima e simples de todos os recursos de autenticação do Laravel, incluindo login, registro, redefinição de senha, verificação de e-mail e confirmação de senha. por trás dele é usado (Tailwind CSS)[https://tailwindcss.com/] e (ApineJS)[https://alpinejs.dev/]. 

## 2.4.1 Instalar o Laravel Breeze.

 Dentro do container *laravelapp-app*  rode os comando abaixo um por vez.

 ```
  composer require laravel/breeze:1.9.4

  php artisan breeze:install

  npm install && npm run dev
 ```

 ## 2.4.2 Testar a autenticação.

 Acesse pelo seu navegador [localhost:8000/register](localhost:8000/register), você verá um formulário de cadastro de usuário.
 
 Faça um cadastro, logo após será direcionado para [localhost:8000/dashboard](localhost:8000/dashboard)

 ## Alterar a rota padrão

 Por padrão o laravel Breeze redireciona usuários autenticado para a rota ```/dashboard```. Vamos alterar para a página inicial.

 abra o arquivo ```routes/web.php``` e adicione a rota abaixo.

 ```
Route::get('/', 'PostController@home')->middleware(['auth'])->name('home');
 ```

 O próximo passo é criar o controle, dentro do container execute o comando abaixo:

 ```
  php artisan make:controller PostController
 ```

Abra o arquivo app/Http/Controllers/PostController.php e adicione o seguinte método, que retorna a view home.

```
public function home() {
    return view('home');
}
```

Acesse  a diretório ```resources/views``` Vamos renomear o arquivo dashboard.blade.php Para: 
home.blade.php.

Abra o arquivo app/Providers/RouteServiceProvider.php 
e altere o nome de ```dashboard``` para ```/``` na variável HOME.

Adicione a linha abaixo: 
```
protected $namespace = 'App\Http\Controllers';
```


Agora, quando o usuário for para  [localhost:8000](localhost:8000) e não estiver logado, ele será redirecionado para o formulário de login. Se estiverem logados, poderão acessar o blog.

Vamos alterar o link para home page nos link de navegação. Acesse o arquivo **resources/views/layouts/navigation.blade.php** e substitua **route('dashbaord')** por **route('home')** em todo o arquivo.
Faça o mesmo, substituindo **{{ __('Dashboard') }}** por **{{ __('Home') }}** 

## 2.5  - Criar nosso CRUD
<br>
Nessa parte veremos como criar um novo modelo que é compatível com MongoDB e realizaremos as operações Create, Read, Update e Delete. 
<br>
### 2.5.1 - criar a migração

Criaremos a migração que criará uma nova coleção de **posts** no banco de dados.

Acesse o container app e rode o comando abaixo:

```
php artisan make:migration create_posts_table
```

Esse comando cria um arquivo no seguinte padrão **YEAR_MONTH_DAY_TIME_create_posts_table** dentro diretorio  **database/migration/YEAR_MONTH_DAY_TIME_create_posts_table** abra-o e adicione as linhas abaixo:

```
 $table->string('title');
 $table->longText('content');
 $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
```
Isso criará uma coleção onde os documentos terão os campos **_id**, **title**, **content**, **user_id**, o user_id atuará como chave estrangeira na tabela posts.

Vamos rodar a migração para criar a estrutura no mongoDB.

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

Definimos os campos preenchíveis como title, content e user_id. Também definimos as datas para ser created_at e updated_at. Por fim, adicionamos um relacionamento belongsTo entre Post e User. 


### 2.5.1 - criar a visualização do Post

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
Apenas estamos apresentando o title, content,  name do user e a data de criação.

Vamos alterar o arquivo home.blade.php para mostrar todos os posts. abra o arquivo em **resources/views/resources/views/** e substitua o conteúdo por:

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
            Não há postagens
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
Agora, se houver alguma postagem, cada uma delas será exibida como cartões. Se não houver nenhuma, a mensagem "Não há postagens" será exibida.

Finalmente, precisamos passar a variável **$posts** do controller  para a view. 
Abra o arquivo **app/Http/Controllers/PostController.php** e altere o método **home** para o seguinte:

```public function home() {
    $posts = Post::with(['user'])->get();
    return view('home', ['posts' => $posts->toArray()]);
}
```

Com essa implementação é possível ler registros do Mongodb da mesma maneira do Mysql usando os mesmo métodos do Eloquent ORM.

### 2.5.2 - criar e atualizar um Post

**Cadastrar um Post**

Para cadastra um post  precisamos de uma rota, um metodo no controller e uma view. Vamos criar uma rota, :

Abra  o arquivo **routes/web.php** a dicione a rota abaixo
```
Route::get('/posts/create','PostController@createForm')->middleware(['auth'])->name('post.form');
```

Vamos adiconar o método createForm no postController, abra o arquivo app/Http/Controllers/PostController.php

```
public function createForm() {
    return view('post_form');
}
```

Nessa parte vamos criar formulário com os campos do post.
crie o arquivo **resources/view/post_form.blade.php**  e adicione o código abaixo:

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

Este formulário já está pronto para ser feito a edição.

Com formulário pronto, vamos adicionar o link na barra de navegação.

Abra o arquivo  **resources/views/layouts/navigation.blade.php** 

Abaixo do comentário <!-- Navigation Links -->, adicione o  código abaixo:

```
<x-nav-link :href="route('post.form')" :active="request()->routeIs('post.form')">
	                 {{ __('Create Post') }}
</x-nav-link>
```
faça o mesmo na div depois de <!-- Responsive Navigation Menu -->
```
 <x-responsive-nav-link :href="route('post.form')" :active="request()->routeIs('post.form')">
	            {{ __('Create Post') }}
            </x-responsive-nav-link>
```

Suba o container, e acesse [localhost:8000](localhost:8000) faça login e verá um link para cadastrar um novo  post, clique nele e veja o formulário.

Para salvaro post é precisa criar uma rota no arquivo web e adicionar o método save no postController. abra o arquivo **routes/web.php** e adicione a rota abaixo:

```
Route::post('/posts/create','PostController@save')->middleware(['auth'])->name('post.save');
```

Agora abra o arquivo **app/Http/Controllers/PostController.php**

importe os seguintes pacates:

```
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
```
 Adicione Também o método save dentro da classe PostController.

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

Esse método valida os campos necessário **title** e o **content**

**Editar um Post**

Formulário de post já estava pronto para ser feita a edição, no início do código nesse trecho```
**{{ isset($post) ? __('Edit Post') : __('Create Post') }}**
```
se a variável post estiver preenchida será mostrado o formulário com os campos preenchidos caso contrário será mostrado o formulário.


Crie o arquivo edit.blade.php em **resources/views/components** e adicione o código abaixo. este trecho mostrar o icone de exclusão.

```
<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
</svg>
```
Vamos agora acrescentar componente de edição no arquivo **resources/views/components/post.blade.php**, abra-o acrescente esta parte:

```
@if(\Auth::user()->_id === $post['user']['_id'])
    <a href="{{ route('post.edit.form', ['id' => $post['_id']]) }}" class="inline-block align-middle pb-1 text-decoration-none text-gray-600">
     @component('components.edit')
     @endcomponent
    </a>
   @endif
```

o arquivo completo ficará assim:
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

Este trecho adicionará o link para editar a postagem somente quando a postagem pertencer ao usuário atual.

Criaremos uma entrada no arquivo **routes/web.php**
adicione a rota:

```
Route::get('/posts/{id}/edit',  'PostController@editForm')->middleware(['auth'])->name('post.edit.form');
```

acesse o controle **app/Http/Controllers/PostController.php**

acrestente o método editform

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

Crie um arquivo **resources/views/components/delete.blade.php**  e acrescente o código abaixo:


```
<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
</svg>
```

Similiar ao editar, adicionaremos no arquivo  
**resources/views/components/post.blade.php** o conteúdo abaixo:

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

para finalizar acrescente ao arquivo **app/Http/Controllers/PostController.php** o método delete.

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


