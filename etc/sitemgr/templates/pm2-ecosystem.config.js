module.exports = {
  apps: [{
    name: '{{DOMAIN}}',
    script: '{{SCRIPT}}',
    interpreter: '/sites/{{DOMAIN}}/.bun/bin/bun',
    cwd: '/sites/{{DOMAIN}}/app',
    env: {
      PORT: {{PORT}},
      NODE_ENV: 'production',
      DB_HOST: 'localhost',
      DB_USERNAME: '{{USERNAME}}',
      DB_PASSWORD: '{{DB_PASSWORD}}',
      DB_DATABASE: '{{USERNAME}}'
    },
    error_file: '/sites/{{DOMAIN}}/logs/pm2-error.log',
    out_file: '/sites/{{DOMAIN}}/logs/pm2-out.log',
    log_file: '/sites/{{DOMAIN}}/logs/pm2-combined.log',
    time: true,
    exec_mode: 'fork',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G'
  }]
};