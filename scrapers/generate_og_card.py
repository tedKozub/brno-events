#!/usr/bin/env python3
import argparse
import asyncio
import html
import os
import sys
from playwright.async_api import async_playwright

def render_html(title: str, uni: str, category: str, date_str: str, location: str, price: str, bg_image: str = None) -> str:
    uni_norm = uni.lower() if uni else "muni"
    uni_class = f"uni-{uni_norm}"

    bg_style = ""
    if bg_image and os.path.exists(bg_image):
        bg_style = f"""
            background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.8) 50%, rgba(15, 23, 42, 0.6) 100%),
                        url('file://{bg_image}') center/cover no-repeat;
        """
    else:
        bg_style = "background: #0f172a;"

    return f"""<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * {{ box-sizing: border-box; }}
  body {{
    margin: 0;
    width: 1200px;
    height: 630px;
    {bg_style}
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #f8fafc;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 65px 75px;
    overflow: hidden;
    position: relative;
  }}
  .bg-glow {{
    position: absolute;
    width: 550px;
    height: 550px;
    background: #4f46e5;
    opacity: 0.16;
    filter: blur(140px);
    border-radius: 50%;
    top: -120px;
    right: -80px;
  }}
  .top-bar {{
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 10;
  }}
  .brand {{
    display: flex;
    align-items: center;
    gap: 14px;
  }}
  .logo-icon {{
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.5);
  }}
  .brand-text {{
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -0.03em;
    color: #ffffff;
  }}
  .brand-highlight {{ color: #818cf8; }}
  .student-badge {{
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #818cf8;
    background: rgba(99, 102, 241, 0.15);
    border: 1px solid rgba(129, 140, 248, 0.3);
    margin-left: 10px;
  }}
  .badge-row {{
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10;
  }}
  .uni-pill {{
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }}
  .uni-muni {{ background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }}
  .uni-vut {{ background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }}
  .uni-mendelu {{ background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }}
  .uni-unob {{ background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }}
  .uni-vetuni {{ background: rgba(192, 132, 252, 0.15); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.3); }}
  .cat-pill {{
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
  }}
  .price-pill {{
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 800;
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #34d399;
  }}
  .main-content {{
    z-index: 10;
    max-width: 1050px;
    margin: auto 0;
  }}
  h1 {{
    font-size: 46px;
    font-weight: 900;
    line-height: 1.18;
    margin: 0 0 24px 0;
    letter-spacing: -0.02em;
    color: #ffffff;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }}
  .meta-pills {{
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
    font-size: 18px;
    font-weight: 700;
    color: #cbd5e1;
  }}
  .meta-item {{
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.06);
    padding: 8px 18px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }}
  .footer-bar {{
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 10;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    font-size: 15px;
    font-weight: 700;
  }}
</style>
</head>
<body>
  <div class="bg-glow"></div>

  <div class="top-bar">
    <div class="brand">
      <div class="logo-icon">
        <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <div style="display: flex; align-items: center;">
        <span class="brand-text">AKCE<span class="brand-highlight">BRNO</span></span>
        <span class="student-badge">Student</span>
      </div>
    </div>
    <div class="badge-row">
      <span class="uni-pill {uni_class}">{html.escape(uni or 'Brno')}</span>
      <span class="cat-pill">{html.escape(category or 'Akce')}</span>
      <span class="price-pill">{html.escape(price or 'Zdarma')}</span>
    </div>
  </div>

  <div class="main-content">
    <h1>{html.escape(title)}</h1>
    <div class="meta-pills">
      <div class="meta-item">
        <svg style="width: 20px; height: 20px; color: #818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{html.escape(date_str or 'Termín upřesněn')}</span>
      </div>
      <div class="meta-item">
        <svg style="width: 20px; height: 20px; color: #818cf8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>{html.escape(location or 'Brno')}</span>
      </div>
    </div>
  </div>

  <div class="footer-bar">
    <span>Studentský kalendář pro Brno</span>
    <span style="color: #cbd5e1;">brnoakce.tadeaskozub.com</span>
  </div>
</body>
</html>
"""

async def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--title", required=True)
    parser.add_argument("--uni", default="MUNI")
    parser.add_argument("--category", default="Akce")
    parser.add_argument("--date", default="")
    parser.add_argument("--location", default="Brno")
    parser.add_argument("--price", default="Zdarma")
    parser.add_argument("--bg", default=None)
    parser.add_argument("--output", required=True)
    args = parser.parse_args()

    os.makedirs(os.path.dirname(os.path.abspath(args.output)), exist_ok=True)

    rendered = render_html(
        title=args.title,
        uni=args.uni,
        category=args.category,
        date_str=args.date,
        location=args.location,
        price=args.price,
        bg_image=args.bg,
    )

    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={"width": 1200, "height": 630})
        await page.set_content(rendered)
        await page.screenshot(path=args.output, type="jpeg", quality=92)
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
