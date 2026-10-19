# Little Poll — Laravel

A responsive session-only voting poll built with Laravel 12, Blade, HTML, and CSS. It emits one manually instrumented OpenTelemetry server span per HTTP request and uses Microsoft's PHP-specific Azure Monitor sidecar walkthrough for Linux App Service code deployments.

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel
- Composer 2

## Run locally

From this folder, install the framework dependencies, create the local environment file, and generate the application key once:

```powershell
$php = 'C:\tools\php82\php.exe'
$phpOptions = @(
	'-d', 'extension_dir=C:\tools\php82\ext',
	'-d', 'extension=php_openssl.dll',
	'-d', 'extension=php_curl.dll',
	'-d', 'extension=php_fileinfo.dll',
	'-d', 'extension=php_mbstring.dll',
	'-d', 'extension=php_pdo_sqlite.dll',
	'-d', 'extension=php_sqlite3.dll',
	'-d', 'extension=php_zip.dll'
)
& $php @phpOptions 'C:\ProgramData\ComposerSetup\bin\composer.phar' install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
& $php @phpOptions .\artisan key:generate
```

After setup, open a new terminal in this folder and start the app with:

```powershell
php artisan serve
```

Open <http://127.0.0.1:8000>. This uses PHP 8.2 from your PATH. Stop the server with `Ctrl+C`. If the PATH change is not visible in an existing terminal, close it and open a new one. `run.bat` remains available as a launcher that selects PHP 8.2 directly.

## OpenTelemetry

`TraceHttpRequests` creates one manual server span per Laravel request, recording its method, matched route, response status, and exceptions. The service provider gets its tracer from the SDK global provider, allowing the PHP SDK to configure OTLP export from environment settings when Composer autoloads with `OTEL_PHP_AUTOLOAD_ENABLED=true`.

The project uses `open-telemetry/sdk`, `open-telemetry/exporter-otlp`, and `php-http/guzzle7-adapter`; `startup.sh` installs/enables the PECL `opentelemetry` extension when absent. No Slim package is included. The Laravel auto-instrumentation package exists, but is not added here because this app already owns its request span and enabling both would duplicate server spans. The app currently exports traces only.

For local checks, `.env.example` enables the SDK and uses the console exporter. Console spans stay local and do not reach Application Insights. The SDK's standard OTLP/HTTP settings are `OTEL_TRACES_EXPORTER=otlp`, `OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf`, and `OTEL_EXPORTER_OTLP_ENDPOINT`; the endpoint is a base URL and the SDK sends to `/v1/traces`.

## Deploy to Azure App Service

Use an **App Service Web App on Linux** with the built-in PHP runtime and code deployment. This project requires PHP 8.2 or newer. Check the currently available App Service runtimes and select a version that satisfies the Composer requirement:

```bash
az webapp list-runtimes --os linux
az webapp config set \
	--resource-group <resource-group> \
	--name <app-name> \
	--linux-fx-version "PHP|<supported-php-version>"
```

Deploy this folder's contents, including `composer.json`, `composer.lock`, `public/`, `default`, and `startup.sh`. Laravel's document root is `public/`; the included NGINX config points to that directory and forwards application routes to `public/index.php`. `SCM_DO_BUILD_DURING_DEPLOYMENT=true` is optional and only needed when the selected deployment method requires App Service Composer build automation.

Set this Startup Command in App Service **Configuration → Stack settings**, following the PHP walkthrough:

```bash
cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default && nginx -s reload && bash /home/site/wwwroot/startup.sh
```

This restores the Laravel-aware NGINX document root and runs the walkthrough startup script, which installs/enables the PECL extension if missing and starts PHP-FPM. Confirm the paths and PHP image assumptions against the selected App Service runtime before applying the command. Deploy a ZIP containing the project files (not an enclosing project directory), for example:

```bash
az webapp deploy \
	--resource-group <resource-group> \
	--name <app-name> \
	--src-path <app-package.zip>
```

Set the following settings on the PHP app under **Settings → Environment variables**:

| Setting | Value | Purpose |
| --- | --- | --- |
| `SCM_DO_BUILD_DURING_DEPLOYMENT` | `true` (optional) | Runs Composer during supported ZIP deployment build automation. |
| `APP_ENV` | `production` | Enables Laravel production mode. |
| `APP_DEBUG` | `false` | Prevents detailed exception pages from being shown to visitors. |
| `APP_KEY` | `<generated Laravel key>` | Laravel encryption key; generate it securely and store it only as an App Service setting. |
| `APP_URL` | `https://<app-name>.azurewebsites.net` | Public application URL. |
| `OTEL_PHP_AUTOLOAD_ENABLED` | `true` | Enables the PHP SDK autoload configuration used by the walkthrough. |
| `OTEL_SERVICE_NAME` | `little-poll-laravel` | Meaningful app-specific service identity for this PHP application. |

The PHP SDK defaults to the OTLP trace exporter, HTTP/protobuf, and `http://localhost:4318`; the walkthrough's sidecar endpoint is `http://127.0.0.1:4318`. The walkthrough's sidecar defaults also include `OTEL_EXPORTER=azuremonitor` and `OTEL_SERVICE_NAME=php-blessed-otel`. Verify which values the portal configures on the app versus the sidecar. App Service app settings may be shared across containers, so do not add a duplicate endpoint or override a sidecar setting without checking container-specific configuration. Use a meaningful service name for this Laravel app.

## Sidecar setup and availability

The user must configure the sidecar manually in the Azure portal:

1. Open **Deployment Center → Containers (new) → Add → Sidecar Extension**.
2. Select **Observability: OpenTelemetry – Azure Monitor**.
3. Enter the Application Insights connection string in the extension configuration. Keep it out of source code.
4. In the sidecar settings, map `OTEL_PHP_AUTOLOAD_ENABLED` from the main app setting using the portal's app-setting reference selector.
5. Verify the sidecar and app OTEL settings, accounting for shared App Service app settings and container-specific values.

Do not assume the named extension is available for every subscription, region, plan, cloud, or App Service Environment. Confirm it is offered for this target app in the portal before deployment. `ApplicationInsightsAgent_EXTENSION_VERSION` is a different legacy managed-agent setting and is not the PHP sidecar or instrumentation path.

The Application Insights connection string is configured through the sidecar portal flow. Do not put it in PHP code, commit it, or unnecessarily expose it to the app container.

If the named extension is unavailable or unsuitable, retain an externally hosted Collector as a fallback. That requires additional hosting, a secure OTLP/HTTP network endpoint and authentication, Collector configuration with the Azure Monitor exporter, and secret management for the Collector's connection string. This is not the default architecture.


## Session behavior

- The poll uses Laravel's web middleware, CSRF protection, and file-backed sessions.
- Submitted picks accumulate in the file-backed session and are shown after each Post/Redirect/Get flow.
- A small browser reload check clears the poll's session tally when the results page is refreshed. The server-side Clear picks button is also available.
- Picks are not accumulated, shared between visitors, or saved to a database.

This demo is suitable for a learning project. Keep real vote aggregation separate from the temporary session display.

## Tests

After installing dependencies, run the feature tests with the same PHP 8.2 executable and extensions used by `run.bat`:

```powershell
& $php @phpOptions .\vendor\bin\phpunit
```

## References

- [Microsoft: PHP App Service OpenTelemetry – Azure Monitor sidecar walkthrough](https://techcommunity.microsoft.com/blog/appsonazureblog/part-i-otel-sidecar-extension-on-azure-app-service-for-linux---intro--php-walkthrough/4469514)
- [Microsoft sample: PHP sidecar app](https://github.com/Azure-Samples/sidecar-samples/tree/main/otel-sidecar/php/php-blessed-app)
- [Microsoft: App Service sidecars overview](https://learn.microsoft.com/en-us/azure/app-service/overview-sidecar)
- [Microsoft: Configure sidecars](https://learn.microsoft.com/en-us/azure/app-service/configure-sidecar)
- [Microsoft: Configure a PHP app in Azure App Service](https://learn.microsoft.com/en-us/azure/app-service/configure-language-php?pivots=platform-linux)
- [Microsoft: Deploy files to Azure App Service](https://learn.microsoft.com/en-us/azure/app-service/deploy-zip)
- [OpenTelemetry PHP: Getting started](https://opentelemetry.io/docs/languages/php/getting-started/)
- [OpenTelemetry PHP: OTLP exporters](https://opentelemetry.io/docs/languages/php/exporters/)
- [OpenTelemetry PHP: Zero-code instrumentation and configuration](https://opentelemetry.io/docs/zero-code/php/auto/)
- [OpenTelemetry PHP: Laravel instrumentation package](https://packagist.org/packages/open-telemetry/opentelemetry-auto-laravel)
- [OpenTelemetry Collector Contrib: Azure Monitor exporter](https://github.com/open-telemetry/opentelemetry-collector-contrib/tree/main/exporter/azuremonitorexporter)
