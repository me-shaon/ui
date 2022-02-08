<?php

namespace Laravel\Ui\Tests\AuthBackend;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

class RegistersUsersTest extends TestCase
{
    use RegistersUsers;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    /** @test */
    public function it_can_register_a_user()
    {
        $request = Request::create('/register', 'POST', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->handleRequestUsing($request, function ($request) {
            return $this->register($request);
        })->assertCreated();

        $this->assertDatabaseHas('users', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
        ]);
    }

    /** @test */
    public function it_checks_if_password_is_too_long()
    {
        $request = Request::create('/register', 'POST', [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => Str::repeat('a', 73),
            'password_confirmation' => Str::repeat('a', 73),
        ],[], [], [
           'HTTP_ACCEPT' => 'application/json',
       ]);

        $response = $this->handleRequestUsing($request, function ($request) {
            return $this->register($request);
        })->assertUnprocessable();

        $this->assertInstanceOf(ValidationException::class, $response->exception);
        $this->assertSame([
              'password' => [
                  'The password must not be greater than 72 characters.',
              ],
        ], $response->exception->errors());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
    }

     /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = (new User())->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->save();

        return $user;
    }

    /**
     * Handle Request using the following pipeline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $callback
     * @return \Illuminate\Testing\TestResponse
     */
    protected function handleRequestUsing(Request $request, callable $callback)
    {
        return new TestResponse(
            (new Pipeline($this->app))
                ->send($request)
                ->through([
                    \Illuminate\Session\Middleware\StartSession::class,
                ])
                ->then($callback)
        );
    }
}
