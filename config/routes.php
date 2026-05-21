<?php
use App\Route;

// Auth
Route::get('/login',   'AuthController@showLogin');
Route::post('/login',  'AuthController@login')->middleware('csrf,rate_limit');
Route::get('/logout',  'AuthController@logout');

// Dashboard
Route::get('/dashboard', 'DashboardController@index')->middleware('auth');
Route::get('/',          'DashboardController@index')->middleware('auth');

// Reservas
Route::get('/reservas',                      'ReservaController@index')->middleware('auth');
Route::get('/reservas/nova',                 'ReservaController@create')->middleware('auth');
Route::post('/reservas',                     'ReservaController@store')->middleware('auth,csrf');
Route::post('/reservas/{id}/confirmar',      'ReservaController@confirm')->middleware('auth,csrf');
Route::post('/reservas/{id}/cancelar',       'ReservaController@cancel')->middleware('auth,csrf');
Route::post('/reservas/{id}/excluir',        'ReservaController@destroy')->middleware('auth,csrf');

// Gráficos
Route::get('/graficos', 'GraficoController@index')->middleware('auth');

// Salas
Route::get('/salas',          'SalaController@index')->middleware('auth');
Route::post('/salas/{id}',    'SalaController@update')->middleware('auth,csrf');

// Usuários
Route::get('/usuarios',              'UsuarioController@index')->middleware('auth');
Route::post('/usuarios',             'UsuarioController@store')->middleware('auth,csrf');
Route::post('/usuarios/{id}',        'UsuarioController@update')->middleware('auth,csrf');
Route::post('/usuarios/{id}/toggle', 'UsuarioController@toggle')->middleware('auth,csrf');

// Recorrências
Route::get('/recorrencias',                    'RecorrenciaController@index')->middleware('auth');
Route::post('/recorrencias/{id}/toggle',       'RecorrenciaController@toggle')->middleware('auth,csrf');
Route::post('/recorrencias/{id}/excluir',      'RecorrenciaController@destroy')->middleware('auth,csrf');

// API
Route::get('/api/events',                      'ApiController@events')->middleware('auth');
Route::post('/api/events/{id}/update',         'ApiController@updateEvent')->middleware('auth,csrf');
Route::get('/api/charts/periodo',  'ApiController@chartPeriodo')->middleware('auth');
Route::get('/api/charts/salas',    'ApiController@chartSalas')->middleware('auth');
Route::get('/api/charts/ocupacao', 'ApiController@chartOcupacao')->middleware('auth');
