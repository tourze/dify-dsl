# Dify DSL 规范文档

## 1. 概述

Dify DSL (Domain-Specific Language) 是一种基于 YAML 的声明式语言，用于定义和编排 AI 应用工作流。它通过一个图（Graph）结构来描述应用的逻辑流程，该图由节点（Nodes）和边（Edges）组成，实现了从简单的问答到复杂的自动化任务的灵活构建。

-   **节点 (Nodes)**：代表工作流中的一个具体操作单元，如调用大语言模型（LLM）、执行代码、检索知识库、调用工具等。
-   **边 (Edges)**：定义了节点之间的连接关系，控制着数据和执行流程的走向。

Dify 支持两种主要的工作流类型：
-   **Chatflow**：面向多轮对话场景，内置聊天记忆，支持在流程中通过 `Answer` 节点进行流式输出。
-   **Workflow**：面向自动化和批处理任务，流程通常是线性的，通过 `End` 节点输出最终结果。

---

## 2. 顶层结构

每个 Dify DSL 文件都包含以下顶层键：

| 键 | 类型 | 描述 | 必须 |
| :--- | :--- | :--- | :--- |
| `app` | Object | 包含应用的元数据，如名称、描述、图标和模式。 | 是 |
| `kind` | String | 定义对象的类型，固定为 `app`。 | 是 |
| `version` | String | DSL 的版本号，例如 `0.1.5` 或 `0.3.0`。 | 是 |
| `workflow` | Object | 定义应用的核心工作流，是 DSL 的主要部分。 | 是 |
| `dependencies` | Array | (可选) 声明应用所依赖的外部插件或 Marketplace 工具。 | 否 |

### 2.1 `app` 对象

| 键 | 类型 | 描述 |
| :--- | :--- | :--- |
| `name` | String | 应用的名称。 |
| `description` | String | 应用的简短描述。 |
| `icon` | String | 应用的图标标识。 |
| `icon_background` | String | 图标的背景颜色（十六进制）。 |
| `mode` | String | 应用模式，常见值为 `advanced-chat` (Chatflow), `workflow` (Workflow), `agent-chat` (Agent聊天)。 |
| `use_icon_as_answer_icon` | Boolean | 是否使用应用图标作为回复头像。 |

### 2.2 `workflow` 对象

| 键 | 类型 | 描述 |
| :--- | :--- | :--- |
| `environment_variables` | Array | 应用的环境变量列表，用于存储 API 密钥等敏感信息。 |
| `conversation_variables` | Array | (仅 Chatflow) 应用的会话变量列表，用于在多轮对话中传递和保持状态。 |
| `features` | Object | 应用启用的附加功能配置，如文件上传、开场白等。 |
| `graph` | Object | 定义工作流的图结构，包含 `nodes` 和 `edges`。 |

---

## 3. 图结构 (`graph`)

图结构是工作流的核心，定义了所有操作单元及其执行顺序。

### 3.1 节点 (`nodes`)

`nodes` 是一个数组，每个元素代表一个节点。节点有多种类型，但它们共享一些通用属性：

-   `id`: (String) 节点的唯一标识符。
-   `data`: (Object) 包含节点具体配置的对象。
    -   `type`: (String) **节点的类型**，这是最关键的字段。
    -   `title`: (String) 节点在编辑器中显示的标题。
    -   `desc`: (String) 节点的描述。
    -   ... 其他特定于类型的属性
-   `position`: (Object) 节点在编辑器画布上的坐标 (`x`, `y`)。

---

## 4. 节点参考

以下是根据官方文档和 YAML 文件分析得出的常见节点类型及其关键属性。

### 4.1 `start` (开始节点)

-   **功能**: 工作流的入口，定义了应用的初始输入变量和系统内置变量。
-   **关键属性 (`data`)**:
    -   `type`: `start`
    -   `variables`: (Array) 用户定义的输入变量数组。每个变量对象包含：
        -   `variable`: (String) 变量名。
        -   `label`: (String) 在 UI 中显示的标签。
        -   `type`: (String) 变量类型，如 `text-input`, `paragraph`, `file`, `file-list`, `select`, `number`。
        -   `required`: (Boolean) 是否为必填项。
        -   `options`: (Array) (仅 `select` 类型) 预设的下拉选项。

- **YAML 示例**:
  ```yaml
  - data:
      type: start
      title: 开始
      variables:
      - variable: q
        label: 查询
        type: paragraph
        required: true
      - variable: scope
        label: 搜索范围
        type: select
        required: true
        options:
        - webpage
        - document
  ```

### 4.2 `llm` (大语言模型节点)

-   **功能**: 调用一个大语言模型来处理文本、生成内容或进行多模态分析。
-   **关键属性 (`data`)**:
    -   `type`: `llm`
    -   `model`: (Object) 定义了模型的 `provider` (供应商), `name` (模型名称) 和 `mode` (模式，如 `chat`)。
    -   `prompt_template`: (Array) 提示词模板，通常包含 `role` (`system`, `user`, `assistant`) 和 `text` (提示词内容)。
    -   `vision`: (Object) (可选) 用于多模态模型，配置图像输入。
        - `enabled`: (Boolean) 是否启用视觉能力。
        - `variable_selector`: (Array) 指定包含图像文件的变量。
    -   `context`: (Object) (可选) 是否启用上下文，并指定上下文来源（通常来自知识检索节点）。
    -   `memory`: (Object) (仅 Chatflow) 配置对话记忆。

- **YAML 示例**:
  ```yaml
  - data:
      type: llm
      title: LLM
      model:
        provider: openai
        name: gpt-4
        mode: chat
      prompt_template:
      - role: system
        text: 你是一个专业的翻译助手。
      - role: user
        text: '请翻译：{{#start.text#}}'
  ```

### 4.3 `tool` (工具节点)

-   **功能**: 调用一个内置或自定义的工具（API），或另一个工作流。
-   **关键属性 (`data`)**:
    -   `type`: `tool`
    -   `provider_id`: (String) 工具的提供者ID。
    -   `provider_type`: (String) 工具类型，如 `api` (自定义API), `builtin` (内置工具), `workflow` (子工作流)。
    -   `tool_name`: (String) 调用的工具名称。
    -   `tool_parameters`: (Object) 传递给工具的参数。值可以通过 `{{#node_id.variable#}}` 的形式引用其他节点的输出。

- **YAML 示例**:
  ```yaml
  - data:
      type: tool
      title: Google 搜索
      provider_id: google_search
      provider_type: builtin
      tool_name: google_search
      tool_parameters:
        query:
          type: variable
          value:
          - start
          - query
  ```

### 4.4 `answer` / `end` (回复/结束节点)

-   **功能**:
    -   `answer`: (仅 Chatflow) 标志着一个分支的结束，并向用户流式返回结果。可用于流程中间步骤。
    -   `end`: (仅 Workflow) 标志着工作流的最终结束，并输出最终结果。
-   **关键属性 (`data`)**:
    -   `type`: `answer` 或 `end`
    -   `answer`: (String) (用于 `answer` 节点) 定义回复给用户的内容，支持变量引用和图文混排。
    -   `outputs`: (Array) (用于 `end` 节点) 定义工作流的最终输出变量列表。每个输出包含 `variable` (变量名) 和 `value_selector` (引用的上游节点和变量)。

- **YAML 示例 (`answer`)**:
  ```yaml
  - data:
      type: answer
      title: 直接回复
      answer: '这是您的搜索结果：{{#llm.text#}}'
  ```
- **YAML 示例 (`end`)**:
  ```yaml
  - data:
      type: end
      title: 结束
      outputs:
      - variable: final_result
        value_selector:
        - llm
        - text
  ```

### 4.5 `knowledge-retrieval` (知识检索)

-   **功能**: 从指定的知识库中检索与查询相关的信息。
-   **关键属性 (`data`)**:
    -   `type`: `knowledge-retrieval`
    -   `query_variable_selector`: (Array) 指定哪个输入变量作为检索的查询语句。
    -   `dataset_ids`: (Array) 要检索的知识库ID列表。
    -   `retrieval_mode`: (String) 检索模式，如 `multiple` (多路召回)。
    -   `multiple_retrieval_config`: (Object) 多路召回的详细配置，如 `top_k` 和 `reranking_model`。

- **YAML 示例**:
  ```yaml
  - data:
      type: knowledge-retrieval
      title: 知识检索
      query_variable_selector:
      - sys.query
      dataset_ids:
      - b7bdaa0d-fdef-4e35-85ab-3146ef7982a6
      retrieval_mode: multiple
  ```

### 4.6 `if-else` (条件分支)

-   **功能**: 根据一个或多个条件来决定工作流的走向。
-   **关键属性 (`data`)**:
    -   `type`: `if-else`
    -   `cases`: (Array) 一个条件分支数组，每个 case 定义了 `case_id` 和 `conditions` (条件列表)。
        -   `conditions`: (Array) 每个条件包含 `variable_selector` (要判断的变量), `comparison_operator` (比较操作符，如 `contains`, `is`, `not empty`) 和 `value` (要比较的值)。

- **YAML 示例**:
  ```yaml
  - data:
      type: if-else
      title: 条件分支
      cases:
      - case_id: 'true'
        conditions:
        - variable_selector:
          - llm
          - text
          comparison_operator: contains
          value: '发票'
  ```

### 4.7 `code` (代码执行)

-   **功能**: 执行一段 Python3 或 Javascript 代码，用于数据转换或自定义逻辑。
-   **关键属性 (`data`)**:
    -   `type`: `code`
    -   `code_language`: (String) 代码语言 (`python3` 或 `javascript`)。
    -   `code`: (String) 要执行的代码字符串。
    -   `variables`: (Array) 代码的输入变量。
    -   `outputs`: (Object) 定义代码执行后的输出变量。

- **YAML 示例**:
  ```yaml
  - data:
      type: code
      title: 代码执行
      code_language: python3
      variables:
      - variable: arg1
        value_selector:
        - http_request_1
        - body
      code: |
        import json
        def main(arg1: str) -> dict:
          data = json.loads(arg1)
          return {"result": data['data']['url']}
      outputs:
        result:
          type: string
  ```

### 4.8 `iteration` (迭代)

-   **功能**: 对一个数组（列表）类型的变量进行循环迭代处理。
-   **关键属性 (`data`)**:
    -   `type`: `iteration`
    -   `iterator_selector`: (Array) 指定要迭代的数组变量。
    -   `output_type`: (String) 迭代完成后输出的变量类型，如 `array[string]`。
    -   `start_node_id`: (String) 迭代内部流程的开始节点ID。

- **YAML 示例**:
  ```yaml
  - data:
      type: iteration
      title: 迭代获取新闻正文
      iterator_selector:
      - code_1
      - result
      output_type: array[string]
      output_selector:
      - llm_2
      - text
  ```

### 4.9 `agent` (Agent)

-   **功能**: 实现自主工具调用，根据推理策略动态选择并执行工具。
-   **关键属性 (`data`)**:
    -   `type`: `agent`
    -   `agent_strategy_name`: (String) Agent 策略名称，如 `function_calling` 或 `ReAct`。
    -   `agent_parameters`: (Object) Agent 的配置参数。
        - `model`: (Object) 驱动 Agent 的 LLM。
        - `tools`: (Array) Agent 可调用的工具列表。
        - `instruction`: (String) Agent 的任务目标和指令。
        - `query`: (String) 用户的输入查询。

- **YAML 示例**:
  ```yaml
  - data:
      type: agent
      title: Agent
      agent_strategy_name: function_calling
      agent_parameters:
        instruction:
          type: constant
          value: 请根据用户输入{{#sys.query#}}调用qiniu完成操作
        model:
          type: constant
          value:
            model: deepseek-R1
            provider: langgenius/volcengine_maas/volcengine_maas
        tools:
          type: constant
          value:
          - enabled: true
            tool_name: qiniu_list_buckets
  ```

---

## 5. 变量与数据流

-   **变量引用**: 在 DSL 中，节点之间的数据传递通过 `{{#node_id.variable_name#}}` 语法实现。
    -   `node_id`: 提供数据的节点的 ID。
    -   `variable_name`: 该节点输出的变量名。
-   **系统变量**: 以 `sys.` 开头，如 `sys.query` (用户输入), `sys.files` (上传的文件)。
-   **环境变量**: 以 `env.` 开头，用于引用在应用中配置的环境变量。
-   **会话变量**: (仅 Chatflow) 以 `conversation.` 开头，用于在多轮对话中保持状态。

---

此文档结合了官方文档和对 85 个实际 YAML 文件的分析，旨在提供一份全面且实用的 Dify DSL 规范。
