import { jsxRenderer } from 'hono/jsx-renderer'

export const renderer = jsxRenderer(({ children }) => {
  return (
    <html lang="ja">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>AI補助金マッチング - あなたに最適な補助金を見つけます</title>
        <meta name="description" content="AIが15,000件の補助金データベースから、あなたにぴったりの補助金をマッチングします。" />
        
        {/* Tailwind CSS */}
        <script src="https://cdn.tailwindcss.com"></script>
        
        {/* Font Awesome Icons */}
        <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet" />
        
        {/* Axios for API calls */}
        <script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>
        
        {/* Custom styles */}
        <link href="/static/style.css" rel="stylesheet" />
        
        {/* Tailwind Config */}
        <script dangerouslySetInnerHTML={{
          __html: `
            tailwind.config = {
              theme: {
                extend: {
                  colors: {
                    'accent-green': '#00FF00',
                    'accent-yellow': '#FFFF00'
                  }
                }
              }
            }
          `
        }} />
      </head>
      <body>
        <div style="display: flex; flex-direction: column; height: 100vh; width: 100%; overflow: hidden;">
          <header class="app-header" style="flex-shrink: 0;">
            <div class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="logo-icon w-10 h-10 flex items-center justify-center text-xl">
                  💡
                </div>
                <div>
                  <h1 class="text-lg font-bold tracking-tight" style="color: #000;">
                    AI補助金マッチング
                  </h1>
                  <p class="text-xs" style="color: #525252;">あなたに最適な補助金を見つけます</p>
                </div>
              </div>
            </div>
          </header>
          <main style="flex: 1; overflow-y: auto; width: 100%;">
            <div class="h-full">
              <div class="app-container h-full">
                {children}
              </div>
            </div>
          </main>
          <footer style="border-top: 1px solid #e5e5e5; background: #fafafa; flex-shrink: 0;">
            <div class="px-4 py-2 text-center">
              <p class="text-xs font-medium tracking-wide" style="color: #525252;">© 2025 AI補助金マッチング</p>
            </div>
          </footer>
        </div>
        <script src="/static/app.js"></script>
      </body>
    </html>
  )
})
