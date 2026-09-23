pipeline {
    agent any

    options {
        disableConcurrentBuilds()
    }

    environment {
        APP_KEY = 'EUKY9laCopF9bkGDF9xvZyClzUAob7Yc'
        DB_PORT = '3306'
        DB_CONNECTION = 'mysql'
        DB_DATABASE = 'firefly'
        DB_USERNAME = 'root'
        DB_PASSWORD = 'root'
        APP_ENV = 'testing'

        // Generate a safe unique prefix based on job name and build number
        SAFE_PREFIX = "${env.JOB_NAME}-${env.BUILD_NUMBER}".toLowerCase().replaceAll("[^a-z0-9_.-]", "-")

        NETWORK_NAME = "net-${SAFE_PREFIX}"
        MYSQL_CONTAINER = "mysql-${SAFE_PREFIX}"
        FIREFLY_CONTAINER = "firefly-${SAFE_PREFIX}"

        DB_HOST = "${MYSQL_CONTAINER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Instal packages') {
            failFast true

            parallel {

                stage('Setup') {
                    agent {
                        docker {
                            image 'composer:2'
                            reuseNode true
                            args '-v composer_cache:/tmp/composer_cache'
                        }
                    }
                    steps {
                        sh '''
                            composer config cache-dir /tmp/composer_cache
                            composer install --no-interaction --ignore-platform-reqs --optimize-autoloader
                        '''
                    }
                }

                stage('Install JS packages') {
                    agent {
                        docker {
                            image 'node:24'
                            args '-v node_cache:/tmp/node_cache'
                            reuseNode true
                        }
                    }
                    steps {
                        sh '''
                            yarn config set cache-folder /tmp/node_cache
                            yarn --frozen-lockfile
                        '''
                    }
                }

            }
        }

        stage('Run linting') {
            failFast true

            parallel {

                stage('Run PHP CS Fixer') {
                    agent {
                        docker {
                            image 'php:8.5'
                            reuseNode true
                        }
                    }
                    steps {
                        sh '''
                            ./vendor/bin/pint
                        '''
                    }
                }

                stage('Run droast') {
                    agent {
                        docker {
                            image 'immanuwell/droast'
                            args  '--entrypoint='
                            reuseNode true
                        }
                    }
                    steps {
                        sh '''
                            droast
                        '''
                    }
                }
            }
        }

        stage('Create Custom Network') {
            steps {
                sh '''
                    echo "Creating Docker network: ${NETWORK_NAME}"
                    docker network create ${NETWORK_NAME} || true
                '''
            }
        }

        stage('Build frontend assets') {
            agent {
                docker {
                    image 'node:24'
                    reuseNode true
                }
            }
            steps {
                sh '''
                    yarn build
                '''
            }
        }

        stage('Start MySQL') {
            steps {
                sh '''
                    docker run -d \
                         --name ${MYSQL_CONTAINER} \
                         --network ${NETWORK_NAME} \
                        -e MYSQL_ROOT_PASSWORD=root \
                        -e MYSQL_DATABASE=firefly \
                        mysql:5.7
                '''
            }
        }

        stage('Wait for MySQL') {
            options {
                timeout(time: 2, unit: 'MINUTES')
            }
            steps {
                sh '''
                    docker run --rm --network ${NETWORK_NAME} mysql:5.7 \
                        bash -c "until mysqladmin ping -h ${MYSQL_CONTAINER} -u root -proot --silent; do sleep 1; done"
                '''
            }
        }

        stage('Start Firefly') {
            steps {
                sh '''
                    docker run -d \
                        --name "${FIREFLY_CONTAINER}" \
                        --network "${NETWORK_NAME}" \
                        -e "APP_KEY=${APP_KEY}" \
                        -e "DB_HOST=${DB_HOST}" \
                        -e "DB_PORT=${DB_PORT}" \
                        -e "DB_CONNECTION=${DB_CONNECTION}" \
                        -e "DB_DATABASE=${DB_DATABASE}" \
                        -e "DB_USERNAME=${DB_USERNAME}" \
                        -e "DB_PASSWORD=${DB_PASSWORD}" \
                        -e "APP_ENV=${APP_ENV}" \
                        fireflyiii/core
                '''
            }
        }

        stage('Wait for Firefly') {
            options {
                timeout(time: 2, unit: 'MINUTES')
            }
            steps {
                sh '''
                    while true; do
                        if docker logs "${FIREFLY_CONTAINER}" 2>&1 | \
                            grep -Fq "Firefly III should be ready for use."; then
                            break
                        fi

                        if [ "$(docker inspect -f '{{.State.Running}}' "${FIREFLY_CONTAINER}")" != "true" ]; then
                            echo "Firefly container stopped before becoming ready"
                            docker logs "${FIREFLY_CONTAINER}"
                            exit 1
                        fi
                        sleep 2
                    done
                '''
            }
        }


        stage('Create initial user') {
            steps {
                sh '''
                    docker exec "${FIREFLY_CONTAINER}" \
                        php artisan system:create-first-user testing@robvankeilegom.be
                '''
            }
        }

        stage('Add OAuth Client & Seed DB') {
            steps {
                sh '''
                    docker run --rm --network ${NETWORK_NAME} mysql:5.7 mysql -h mysql -u root -proot -e "INSERT INTO firefly.user_groups (id, created_at, updated_at, deleted_at, title) VALUES (null, '2022-01-01 00:00:00', '2022-01-01 00:00:00', NULL, 'testing@robvankeilegom.be');"

                    docker run --rm --network ${NETWORK_NAME} mysql:5.7 mysql -h mysql -u root -proot -e "INSERT INTO firefly.oauth_clients (id, user_id, name, secret, redirect, personal_access_client, password_client, revoked, created_at, updated_at, provider) VALUES (3, 1, 'Firefly III Password Access Client', '2C1EMlc8wiMpcheWOGi0829puwgFutt6TjDsptM2', 'http://localhost', '0', '1', '0', '2022-01-01 00:00:00', '2022-01-01 00:00:00', 'users');"

                    docker run --rm --network ${NETWORK_NAME} mysql:5.7 mysql -h mysql -u root -proot -e "INSERT INTO firefly.accounts (id, created_at, updated_at, deleted_at, user_id, user_group_id, account_type_id, name, virtual_balance, iban, active, encrypted) VALUES ('1', '2022-01-01 00:00:00', '2022-01-01 00:00:00', NULL, '1', NULL, '3', 'Paypal', NULL, NULL, '1', '0');"

                    docker run --rm --network ${NETWORK_NAME} mysql:5.7 mysql -h mysql -u root -proot -e "UPDATE firefly.users SET password = '\$2y\$10\$Y10M8.t2HhRk0JY5fqfJc.fS9GORgDgvywcwd35jiu0/EJnSFkvui', user_group_id = 1;"
                '''
            }
        }

        stage('Create Personal Access Token') {
            steps {
                sh '''
                    docker run --rm --network ${NETWORK_NAME} alpine:3.18 sh -c "
                        apk --no-cache add curl jq > /dev/null
                        curl -fsSL --request POST 'http://${FIREFLY_CONTAINER}:8080/oauth/token' \\
                        --header 'Accept: application/json' \\
                        --form 'grant_type=\"password\"' \\
                        --form 'client_id=\"3\"' \\
                        --form 'client_secret=\"2C1EMlc8wiMpcheWOGi0829puwgFutt6TjDsptM2\"' \\
                        --form 'username=\"testing@robvankeilegom.be\"' \\
                        --form 'password=\"H0plcPOsi5LDmBKxPTBUdubj\"' | jq '.access_token'
                    " > token.txt

                    echo "FIREFLY_TOKEN=$(cat token.txt)" > .env
                '''
            }
        }

        stage('Run Tests (Pest)') {
            agent {
                docker {
                    image 'robvankeilegom/laravel-fpm:8.5'
                    args '--network ${NETWORK_NAME}'
                    reuseNode true
                }
            }

            environment {
                FIREFLY_PAYPAL_ACCOUNT_ID = "1"
                FIREFLY_URI = "${FIREFLY_CONTAINER}:8080"
            }

            steps {
                sh '''
                    cp .env.example .env
                    php artisan key:generate
                    rm -f storage/database.sqlite
                    touch storage/database.sqlite
                    composer install --no-interaction --ignore-platform-reqs
                    ./vendor/bin/pest
                    '''
            }
        }
    }

    post {
        always {
            cleanWs()

            sh '''
                echo "Cleaning up containers and network..."
                docker rm -f ${MYSQL_CONTAINER} ${FIREFLY_CONTAINER} || true
                docker network rm ${NETWORK_NAME} || true
            '''
        }
    }
}
