# 软件著作权申请 - 材料准备清单

**软件名称**: AI 短剧制作系统 V1.0  
**生成日期**: 2026 年 04 月 18 日

---

## ✅ 已生成材料

| 文件 | 状态 | 路径 |
|------|------|------|
| 申请材料总览 | ✅ 已生成 | docs/软件著作权申请材料.md |
| 本说明文档 | ✅ 已生成 | docs/soft_copyright/README.md |

---

## 📋 待准备材料

### 1️⃣ 身份证明文件

**个人申请**：
- [ ] 身份证正反面扫描件（PDF）
- [ ] 文件名：`01_身份证.pdf`

**企业申请**：
- [ ] 营业执照副本扫描件（PDF）
- [ ] 文件名：`01_营业执照.pdf`

---

### 2️⃣ 源代码文档（60 页）

**格式要求**：
- PDF 格式
- 每页 50 行代码
- 页眉：软件名称 + 版本号
- 页脚：页码（1/60, 2/60...）
- 代码开头有注释

**已准备代码文件**：
```
backend/app/controller/
├── UserController.php      (约 350 行) ✅
├── AiController.php        (约 400 行) ✅
├── WorksController.php     (约 300 行) ✅
├── OrderController.php     (约 400 行) ✅
└── MaterialController.php  (约 150 行) ✅

backend/app/service/
├── AiService.php           (约 350 行) ✅
└── UserService.php         (约 200 行) ✅

frontend/pages/
├── index/index.vue         (约 400 行) ✅
├── create/index.vue        (约 450 行) ✅
├── user/index.vue          (约 400 行) ✅
└── vip/index.vue           (约 350 行) ✅

frontend/api/
├── index.js                (约 250 行) ✅
└── request.js              (约 200 行) ✅
```

**生成源代码 PDF 命令**：
```bash
# 使用以下脚本生成 PDF
cd /home/admin/openclaw/workspace/ai-drama-system
python3 docs/soft_copyright/generate_source_code_pdf.py
```

---

### 3️⃣ 用户手册/设计文档（含截图）

**内容要求**：
- 软件介绍（1 页）
- 运行环境（1 页）
- 安装部署（2 页）
- 功能说明（每模块 1-2 页）
- 运行截图（至少 6 张）

**待准备截图**：
- [ ] 登录/注册页面
- [ ] 首页界面
- [ ] AI 创作页面
- [ ] 作品列表页面
- [ ] 会员中心页面
- [ ] 后台管理页面

**建议尺寸**：
- 截图宽度：1200-1600 像素
- 格式：PNG 或 JPG
- 清晰显示软件名称和功能

---

### 4️⃣ 申请表

**在线填写**：
1. 访问：https://register.ccopyright.com.cn
2. 注册/登录账号
3. 选择"计算机软件著作权登记申请"
4. 按页面提示填写

**关键信息**（提前准备）：
- 软件全称：AI 短剧制作系统
- 版本号：V1.0
- 软件分类：应用软件 - 多媒体制作软件
- 开发完成日期：2026 年 04 月 18 日
- 开发方式：独立开发
- 权利范围：全部权利

---

## 🛠️ 辅助工具

### 生成源代码 PDF 脚本

创建文件：`docs/soft_copyright/generate_source_code_pdf.py`

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
软件著作权申请 - 源代码 PDF 生成脚本
"""

import os
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# 注册中文字体
pdfmetrics.registerFont(TTFont('SimSun', '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc'))

def generate_code_pdf(output_path='source_code.pdf'):
    """生成源代码 PDF"""
    c = canvas.Canvas(output_path, pagesize=A4)
    width, height = A4
    
    # 页边距
    margin_left = 50
    margin_right = 50
    margin_top = 50
    margin_bottom = 50
    
    # 每页行数
    lines_per_page = 50
    
    # 收集所有代码文件
    code_files = [
        'backend/app/controller/UserController.php',
        'backend/app/controller/AiController.php',
        # ... 添加更多文件
    ]
    
    all_lines = []
    
    # 读取代码
    for file_path in code_files:
        full_path = os.path.join('../../', file_path)
        if os.path.exists(full_path):
            with open(full_path, 'r', encoding='utf-8') as f:
                lines = f.readlines()
                # 添加文件注释头
                all_lines.append(f"// 文件：{file_path}\n")
                all_lines.extend(lines)
                all_lines.append("\n")
    
    # 分页写入 PDF
    page_num = 1
    line_num = 0
    
    for line in all_lines:
        if line_num >= lines_per_page:
            # 写入页脚
            c.setFont('SimSun', 9)
            c.drawString(width/2 - 20, margin_bottom - 15, f"{page_num}")
            
            # 新页面
            c.showPage()
            page_num += 1
            line_num = 0
        
        # 写入页眉
        if line_num == 0:
            c.setFont('SimSun', 10)
            c.drawString(margin_left, height - 20, "AI 短剧制作系统 V1.0 - 源代码")
        
        # 写入代码
        c.setFont('Courier', 8)
        y_position = height - margin_top - (line_num * 12)
        c.drawString(margin_left, y_position, line.rstrip()[:100])  # 限制每行长度
        
        line_num += 1
    
    # 最后一页页脚
    c.setFont('SimSun', 9)
    c.drawString(width/2 - 20, margin_bottom - 15, f"{page_num}")
    
    c.save()
    print(f"PDF 已生成：{output_path}，共{page_num}页")

if __name__ == '__main__':
    generate_code_pdf()
```

---

## 📤 提交流程

### 步骤 1：准备材料（1-2 天）
- [ ] 填写著作权人信息
- [ ] 扫描身份证明
- [ ] 生成源代码 PDF
- [ ] 准备用户手册（含截图）

### 步骤 2：在线提交（1 天）
- [ ] 注册版权中心账号
- [ ] 在线填写申请表
- [ ] 上传所有材料
- [ ] 缴纳费用

### 步骤 3：等待审批（30-40 工作日）
- [ ] 关注审核状态
- [ ] 如需补正，及时修改

### 步骤 4：领取证书
- [ ] 下载电子证书
- [ ] 接收纸质证书

---

## 💰 费用预算

| 项目 | 金额 |
|------|------|
| 官费（个人） | 300 元 |
| 代理费（可选） | 500-800 元 |
| **合计** | **800-1100 元** |

*如选择加急服务，费用另计*

---

## 📞 咨询方式

- **中国版权保护中心官网**: https://www.ccopyright.com.cn
- **咨询电话**: 010-68003887
- **办公时间**: 工作日 9:00-11:30, 13:30-16:30

---

**下一步**：
1. 确认著作权人信息（个人/企业）
2. 准备身份证明扫描件
3. 运行脚本生成源代码 PDF
4. 准备用户手册和截图
5. 登录官网提交申请
