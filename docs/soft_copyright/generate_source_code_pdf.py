#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
软件著作权申请 - 源代码 PDF 生成脚本

使用方法:
    python3 generate_source_code_pdf.py

输出:
    source_code_front.pdf (前端代码 30 页)
    source_code_back.pdf  (后端代码 30 页)
"""

import os
import sys
from datetime import datetime

# 检查是否安装 reportlab
try:
    from reportlab.lib.pagesizes import A4
    from reportlab.pdfgen import canvas
    from reportlab.pdfbase import pdfmetrics
    from reportlab.pdfbase.ttfonts import TTFont
    from reportlab.lib.units import mm
except ImportError:
    print("错误：请安装 reportlab 库")
    print("运行：pip install reportlab")
    sys.exit(1)

# 配置
SOFTWARE_NAME = "AI 短剧制作系统"
VERSION = "V1.0"
LINES_PER_PAGE = 50
FONT_SIZE = 9
LINE_HEIGHT = 11

# 代码文件列表
BACKEND_FILES = [
    'backend/app/controller/UserController.php',
    'backend/app/controller/AiController.php',
    'backend/app/controller/WorksController.php',
    'backend/app/controller/OrderController.php',
    'backend/app/controller/MaterialController.php',
    'backend/app/service/AiService.php',
    'backend/app/service/UserService.php',
    'backend/app/middleware/AuthMiddleware.php',
    'backend/app/model/User.php',
    'backend/app/BaseController.php',
]

FRONTEND_FILES = [
    'frontend/pages/index/index.vue',
    'frontend/pages/create/index.vue',
    'frontend/pages/user/index.vue',
    'frontend/pages/vip/index.vue',
    'frontend/api/index.js',
    'frontend/api/request.js',
    'frontend/pages.json',
    'frontend/manifest.json',
]

def find_font_path():
    """查找可用的中文字体"""
    font_paths = [
        '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
        '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
        '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
        '/System/Library/Fonts/PingFang.ttc',  # macOS
        'C:\\Windows\\Fonts\\simhei.ttf',  # Windows
        'C:\\Windows\\Fonts\\simsun.ttc',
    ]
    
    for path in font_paths:
        if os.path.exists(path):
            return path
    
    return None

def read_file_lines(file_path, base_dir):
    """读取文件内容"""
    full_path = os.path.join(base_dir, file_path)
    if not os.path.exists(full_path):
        print(f"警告：文件不存在 - {file_path}")
        return []
    
    with open(full_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    # 添加文件头注释
    header = [
        f"// =====================================================\n",
        f"// 软件名称：{SOFTWARE_NAME}\n",
        f"// 版本号：{VERSION}\n",
        f"// 文件：{file_path}\n",
        f"// =====================================================\n",
        f"\n",
    ]
    
    return header + lines

def generate_pdf(output_path, file_list, base_dir, max_pages=30):
    """生成 PDF 文档"""
    
    # 查找字体
    font_path = find_font_path()
    use_chinese_font = False
    
    if font_path:
        try:
            pdfmetrics.registerFont(TTFont('ChineseFont', font_path))
            use_chinese_font = True
            print(f"使用中文字体：{font_path}")
        except Exception as e:
            print(f"字体加载失败：{e}")
    
    c = canvas.Canvas(output_path, pagesize=A4)
    width, height = A4
    
    # 页边距（毫米转点）
    margin_left = 20 * mm
    margin_right = 20 * mm
    margin_top = 20 * mm
    margin_bottom = 20 * mm
    
    # 可用宽度
    content_width = width - margin_left - margin_right
    
    # 收集所有代码行
    all_code = []
    
    for file_path in file_list:
        lines = read_file_lines(file_path, base_dir)
        if lines:
            # 添加文件分隔符
            all_code.append(f"\n// ============ {file_path} ============\n\n")
            all_code.extend(lines)
            all_code.append("\n\n")
    
    print(f"共收集 {len(all_code)} 行代码")
    
    # 分页写入
    page_num = 1
    line_num = 0
    total_lines = 0
    
    for line in all_code:
        if line_num >= LINES_PER_PAGE or total_lines >= max_pages * LINES_PER_PAGE:
            # 写入页脚
            if use_chinese_font:
                c.setFont('ChineseFont', 8)
            else:
                c.setFont('Helvetica', 8)
            c.drawCentredString(width / 2, margin_bottom - 10 * mm, str(page_num))
            
            # 新页面
            c.showPage()
            page_num += 1
            line_num = 0
            
            if page_num > max_pages:
                break
        
        # 写入页眉
        if line_num == 0:
            if use_chinese_font:
                c.setFont('ChineseFont', 10)
            else:
                c.setFont('Helvetica-Bold', 10)
            header_text = f"{SOFTWARE_NAME} {VERSION} - 源代码 (第{page_num}页/共{max_pages}页)"
            c.drawString(margin_left, height - 15 * mm, header_text)
        
        # 写入代码行
        if use_chinese_font:
            c.setFont('ChineseFont', FONT_SIZE)
        else:
            c.setFont('Courier', FONT_SIZE)
        
        y_position = height - margin_top - (line_num * LINE_HEIGHT)
        
        # 截断过长的行
        line_text = line.rstrip('\n\r')
        if len(line_text) > 120:
            line_text = line_text[:120] + '...'
        
        try:
            c.drawString(margin_left, y_position, line_text)
        except Exception as e:
            # 如果中文渲染失败，尝试 ASCII
            c.setFont('Courier', FONT_SIZE)
            ascii_line = line_text.encode('ascii', 'ignore').decode('ascii')
            c.drawString(margin_left, y_position, ascii_line)
        
        line_num += 1
        total_lines += 1
    
    # 最后一页页脚
    if page_num <= max_pages:
        if use_chinese_font:
            c.setFont('ChineseFont', 8)
        else:
            c.setFont('Helvetica', 8)
        c.drawCentredString(width / 2, margin_bottom - 10 * mm, str(page_num))
    
    c.save()
    print(f"PDF 已生成：{output_path}")
    print(f"  - 页数：{min(page_num, max_pages)}页")
    print(f"  - 代码行数：{total_lines}行")
    
    return min(page_num, max_pages)

def main():
    """主函数"""
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    output_dir = os.path.join(base_dir, 'docs', 'soft_copyright')
    
    os.makedirs(output_dir, exist_ok=True)
    
    print("=" * 60)
    print(f"{SOFTWARE_NAME} {VERSION}")
    print("软件著作权申请 - 源代码 PDF 生成")
    print("=" * 60)
    print()
    
    # 生成后端代码 PDF
    print("正在生成后端代码 PDF...")
    back_pages = generate_pdf(
        os.path.join(output_dir, 'source_code_back.pdf'),
        BACKEND_FILES,
        base_dir,
        max_pages=30
    )
    
    # 生成前端代码 PDF
    print()
    print("正在生成前端代码 PDF...")
    front_pages = generate_pdf(
        os.path.join(output_dir, 'source_code_front.pdf'),
        FRONTEND_FILES,
        base_dir,
        max_pages=30
    )
    
    print()
    print("=" * 60)
    print("生成完成！")
    print("=" * 60)
    print()
    print(f"输出文件:")
    print(f"  - 后端代码：{output_dir}/source_code_back.pdf ({back_pages}页)")
    print(f"  - 前端代码：{output_dir}/source_code_front.pdf ({front_pages}页)")
    print()
    print("下一步:")
    print("  1. 检查 PDF 内容是否完整")
    print("  2. 准备身份证明文件")
    print("  3. 准备用户手册（含运行截图）")
    print("  4. 登录 https://register.ccopyright.com.cn 提交申请")
    print()

if __name__ == '__main__':
    main()
