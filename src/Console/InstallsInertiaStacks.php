<?php

namespace Laravel\Breeze\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallsInertiaStacks
{
    /**
     * Install the Inertia React Breeze stack.
     *
     * @return int|null
     */
    protected function installInertiaReactStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^1.0', 'laravel/sanctum:^4.0', 'tightenco/ziggy:^2.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@headlessui/react' => '^2.0.0',
                '@inertiajs/react' => '^1.0.0',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-react' => '^4.2.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.2.1',
                'react' => '^18.2.0',
                'react-dom' => '^18.2.0',
            ] + $packages;
        });

        $this->updateNodePackages(function ($packages) {
            return [
                    '@types/node' => '^18.13.0',
                    '@types/react' => '^18.0.28',
                    '@types/react-dom' => '^18.0.10',
                    'typescript' => '^5.0.2',
                ] + $packages;
        });

        if ($this->option('eslint')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    'eslint' => '^8.57.0',
                    'eslint-plugin-react' => '^7.34.4',
                    'eslint-plugin-react-hooks' => '^4.6.2',
                    'eslint-plugin-prettier' => '^5.1.3',
                    'eslint-config-prettier' => '^9.1.0',
                    'prettier' => '^3.3.0',
                    'prettier-plugin-organize-imports' => '^4.0.0',
                    'prettier-plugin-tailwindcss' => '^0.6.5',
                ] + $packages;
            });

            $this->updateNodePackages(function ($packages) {
                return [
                        '@typescript-eslint/eslint-plugin' => '^7.16.0',
                        '@typescript-eslint/parser' => '^7.16.0',
                    ] + $packages;
            });

            $this->updateNodeScripts(function ($scripts) {
                return $scripts + [
                        'lint' => 'eslint resources/js --ext .js,.jsx,.ts,.tsx --ignore-path .gitignore --fix',
                    ];
            });

            copy(__DIR__.'/../../stubs/inertia-react-ts/.eslintrc.json', base_path('.eslintrc.json'));

            copy(__DIR__.'/../../stubs/inertia-common/.prettierrc', base_path('.prettierrc'));
        }

        // Providers...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Providers', app_path('Providers'));

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Http/Controllers', app_path('Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(app_path('Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', app_path('Http/Requests'));

        // Middleware...
        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        (new Filesystem)->ensureDirectoryExists(app_path('Http/Middleware'));
        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', app_path('Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-react/resources/views/app.blade.php', resource_path('views/app.blade.php'));

        @unlink(resource_path('views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Components'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(resource_path('js/Pages'));

        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Components', resource_path('js/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Layouts', resource_path('js/Layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Pages', resource_path('js/Pages'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/types', resource_path('js/types'));

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        if ($this->option('pest')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/pest-tests/Feature', base_path('tests/Feature'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/tests/Feature', base_path('tests/Feature'));
        }

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', resource_path('css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-react-ts/vite.config.js', base_path('vite.config.js'));

        copy(__DIR__.'/../../stubs/inertia-react-ts/tsconfig.json', base_path('tsconfig.json'));
        copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/app.tsx', resource_path('js/app.tsx'));

        if (file_exists(resource_path('js/bootstrap.js'))) {
            rename(resource_path('js/bootstrap.js'), resource_path('js/bootstrap.ts'));
        }

        $this->replaceInFile('"vite build', '"tsc && vite build', base_path('package.json'));
        $this->replaceInFile('.jsx', '.tsx', base_path('vite.config.js'));
        $this->replaceInFile('.jsx', '.tsx', resource_path('views/app.blade.php'));
        $this->replaceInFile('.vue', '.tsx', base_path('tailwind.config.js'));

        if (file_exists(resource_path('js/app.js'))) {
            unlink(resource_path('js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installInertiaReactSsrStack();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Install the Inertia React SSR stack into the application.
     *
     * @return void
     */
    protected function installInertiaReactSsrStack()
    {
        copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/ssr.tsx', resource_path('js/ssr.tsx'));
        $this->replaceInFile("input: 'resources/js/app.tsx',", "input: 'resources/js/app.tsx',".PHP_EOL."            ssr: 'resources/js/ssr.tsx',", base_path('vite.config.js'));
        $this->configureReactHydrateRootForSsr(resource_path('js/app.tsx'));

        $this->configureZiggyForSsr();

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('.gitignore'));
    }

    /**
     * Configure the application JavaScript file to utilize hydrateRoot for SSR.
     *
     * @param  string  $path
     * @return void
     */
    protected function configureReactHydrateRootForSsr($path)
    {
        $this->replaceInFile(
            <<<'EOT'
            import { createRoot } from 'react-dom/client';
            EOT,
            <<<'EOT'
            import { createRoot, hydrateRoot } from 'react-dom/client';
            EOT,
            $path
        );

        $this->replaceInFile(
            <<<'EOT'
                    const root = createRoot(el);

                    root.render(<App {...props} />);
            EOT,
            <<<'EOT'
                    if (import.meta.env.SSR) {
                        hydrateRoot(el, <App {...props} />);
                        return;
                    }

                    createRoot(el).render(<App {...props} />);
            EOT,
            $path
        );
    }

    /**
     * Configure Ziggy for SSR.
     *
     * @return void
     */
    protected function configureZiggyForSsr()
    {
        $this->replaceInFile(
            <<<'EOT'
            use Inertia\Middleware;
            EOT,
            <<<'EOT'
            use Inertia\Middleware;
            use Tighten\Ziggy\Ziggy;
            EOT,
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        $this->replaceInFile(
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
            EOT,
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
                        'ziggy' => fn () => [
                            ...(new Ziggy)->toArray(),
                            'location' => $request->url(),
                        ],
            EOT,
            app_path('Http/Middleware/HandleInertiaRequests.php')
        );

        $this->replaceInFile(
            <<<'EOT'
                export interface User {
                EOT,
            <<<'EOT'
                import { Config } from 'ziggy-js';

                export interface User {
                EOT,
            resource_path('js/types/index.d.ts')
        );

        $this->replaceInFile(
            <<<'EOT'
                    auth: {
                        user: User;
                    };
                EOT,
            <<<'EOT'
                    auth: {
                        user: User;
                    };
                    ziggy: Config & { location: string };
                EOT,
            resource_path('js/types/index.d.ts')
        );
    }
}
